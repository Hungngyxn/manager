<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use App\Imports\OrderFulfillFeeImport;
use App\Models\Log;
use App\Models\Order;
use App\Models\SellerHasShop;
use App\Models\Sku;
use App\Models\TiktokToken;
use App\Models\User;
use App\Services\OrderService;
use App\Services\SkuService;
use App\Services\TikTokService;
use DB;
use Http;
use Illuminate\Http\Request;
use App\Imports\OrderImport;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
	protected $tiktok;
	protected $skuParser;

	public function __construct(TikTokService $tiktok, SkuService $skuParser)
	{
		$this->middleware('auth');
		$this->tiktok = $tiktok;
		$this->skuParser = $skuParser;
	}

	public function sync()
	{
		DB::beginTransaction();

		try {
			$shops = SellerHasShop::with('seller', 'token')->get();

			foreach ($shops as $shop) {
				if (!$shop->user_id)
					continue;

				$token = TiktokToken::where('shop_name', $shop->shop_name)->first();
				if (!$token || !$token->access_token || !$token->expires_at) {
					continue;
				}

				$client = $this->tiktok->client();
				$client->setAccessToken($token->access_token);
				$client->setShopCipher($shop->shop_cipher);

				$orders = $this->tiktok->fetchOrderList($client);
				$this->tiktok->syncOrders($orders, $shop->shop_cipher);
			}

			DB::commit();
			return redirect()->route('orders.index')->with('status', 'Order synchronization successful!');
		} catch (\Exception $e) {
			DB::rollBack();
			return redirect()->route('orders.index')->with('error', 'Order synchronization failed: ' . $e->getMessage());
		}
	}

	public function index(Request $request)
	{
		// Nội dung trang /orders giờ là bảng ShopUS (board) — xem ShopUsController@board.
		// Code listing order cũ được giữ ở indexLegacy() để có thể khôi phục.
		return app(ShopUsController::class)->board($request);
	}

	public function indexLegacy(Request $request)
	{
		$perPage = $request->get('perPage', 10);
		$query = Order::query()->with('skuInfo');

		$user = auth()->user();
		$role = $user->role->name;

		$sellers = [];
		$shopNames = [];

		if ($role === 'Seller') {
			$shopFromOrders = Order::where('user_id', $user->id)->pluck('shop_name');
			$shopFromRelation = $user->shops()->pluck('shop_name');

			$shopNames = $shopFromOrders
				->merge($shopFromRelation)
				->filter()
				->unique()
				->values();

			$query->where('user_id', $user->id);
		} else {
			$shopNames = SellerHasShop::pluck('shop_name')->unique();
			$sellers = User::select('id', 'name')->distinct('name')->get();
		}

		if ($request->filled('user_id')) {
			$query->where('user_id', $request->user_id);
		}

		if ($request->filled('shop_name')) {
			$query->where('shop_name', $request->shop_name);
		}

		if ($search = $request->input('search')) {
			$query->where(function ($q) use ($search) {
				$q->where('sku', 'like', "%$search%")
					->orWhere('extra_id', 'like', "%$search%")
					->orWhere('order_id', 'like', "%$search%")
					->orWhere('shop_name', 'like', "%$search%");
			});
		}

		if ($request->filled('missing_sku')) {
			$query->whereDoesntHave('skuInfo');
		}

		if ($request->filled('date_start')) {
			$query->whereDate('created_at', '>=', $request->date_start);
		}

		if ($request->filled('date_end')) {
			$query->whereDate('created_at', '<=', $request->date_end);
		}

		$orders = $query->orderBy('created_at', 'desc')
			->paginate($perPage)
			->appends($request->only([
				'search',
				'user_id',
				'shop_name',
				'date_start',
				'date_end'
			]));

		$skus = Sku::all();
		$orderCount = $query->count();

		return view('pages.order.index', compact(
			'orders',
			'sellers',
			'shopNames',
			'skus',
			'orderCount'
		));
	}

	public function create()
	{
		$shops = SellerHasShop::all();
		$skus = Sku::all();

		return view('pages.order.create', compact('shops', 'skus'));
	}

	public function store(Request $request)
	{
		$validated = $request->validate([
			'extra_id' => 'required|string|max:255',
			'order_id' => 'required|string|max:255',
			'sku' => 'required|string|max:255',
			'shop_name' => 'required|string|max:255',
			'quantity' => 'required|numeric|min:1',
			'total' => 'required|numeric|min:0',
			'user_id' => 'required|exists:users,id',
		]);

		DB::beginTransaction();

		try {
			$parsed = $this->skuParser->parseSkuWithSkuOrder($validated['sku'], $validated['quantity']);
			$skuCode = $parsed['sku'];
			$quantity = $parsed['quantity'];

			if ($this->isDuplicateOrder($validated['extra_id'], $skuCode, $validated['order_id'])) {
				return redirect()->back()->with('error', 'Order already exists.');
			}

			$sku = Sku::whereRaw('LOWER(sku) = ?', [$skuCode])->firstOrFail();

			if ($sku->quantity < $quantity) {
				return redirect()->back()->with('error', 'Not enough stock for SKU: ' . $skuCode);
			}

			// Tính toán cost, profit, bonus
			$service = new OrderService($sku, $quantity, $validated['total']);
			$calc = $service->calculate();

			$order = new Order([
				'extra_id' => strtolower($validated['extra_id']),
				'order_id' => $validated['order_id'],
				'sku' => $skuCode,
				'shop_name' => strtolower($validated['shop_name']),
				'quantity' => $quantity,
				'total' => $validated['total'],
				'user_id' => $validated['user_id'],
				'cost' => $calc['cost'],
				'profit' => $calc['profit'],
				'bonus' => $calc['bonus'],
			]);
			$order->save();

			// Trừ tồn kho
			$sku->decrement('quantity', $quantity);

			ReportController::aggregateForDate($order->user_id, $order->created_at->toDateString());

			DB::commit();

			return redirect()->route('orders.index')->with('status', 'Order created successfully!');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->back()->with('error', 'Error creating order: ' . $e->getMessage());
		}
	}


	public function edit(Order $order)
	{
		return view('pages.order.edit', compact('order'));
	}

	public function update(Request $request, Order $order)
	{
		try {
			$validated = $request->validate([
				'order_id' => 'required|string|max:255',
				'sku' => 'required|string|max:255',
				'quantity' => 'required|numeric|min:1',
				'total' => 'required|numeric|min:0',
			]);

		} catch (ValidationException $e) {
			return redirect()->back()->with('error', 'Error updating order: ' . $e->getMessage());
		}

		DB::beginTransaction();

		try {
			$skuCode = $validated['sku'];
			$quantity = $validated['quantity'];

			$sku = Sku::where('sku', $validated['sku'])->firstOrFail();

			$service = new OrderService($sku, $quantity, $validated['total'], $validated['fulfill_fee'] ?? 0);
			$calc = $service->calculate();

			$order->sku = $skuCode;
			$order->quantity = $quantity;
			$order->total = $validated['total'];
			$order->cost = $calc['cost'];
			$order->profit = $calc['profit'];
			$order->bonus = $calc['bonus'];

			$order->save();

			ReportController::aggregateForDate($order->user_id, $order->created_at->toDateString());

			DB::commit();

			return redirect()->back()->with('status', 'Order ' . $validated['order_id'] . ' updated successfully!');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->back()->with('error', 'Error updating order: ' . $e->getMessage());
		}
	}

	public function destroy(Order $order)
	{
		DB::beginTransaction();

		try {
			$userId = $order->user_id;
			$date = $order->created_at->toDateString();

			$order->delete();

			ReportController::aggregateForDate($userId, $date);

			DB::commit();

			return redirect()->back()->with('status', 'Order deleted successfully.');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->back()->with('error', 'Error deleting order: ' . $e->getMessage());
		}
	}

	public function delete(Request $request)
	{
		$orderIds = $request->input('order_ids');

		if (!$orderIds || !is_array($orderIds)) {
			return redirect()->back()->with('error', 'No orders selected.');
		}

		try {
			DB::beginTransaction();

			$orders = Order::whereIn('id', $orderIds)->get();

			$grouped = $orders->groupBy(function ($order) {
				return $order->user_id . '|' . $order->created_at->toDateString();
			});

			Order::whereIn('id', $orderIds)->delete();

			foreach ($grouped as $key => $group) {
				[$userId, $date] = explode('|', $key);

				if ($userId !== 'Unassigned') {
					ReportController::aggregateForDate($userId, $date);
				}
			}

			DB::commit();

			return redirect()->route('orders.index')->with('status', 'Orders deleted successfully.');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->back()->with('error', 'Error deleting orders: ' . $e->getMessage());
		}
	}


	public function importForm()
	{
		return view('orders.import');
	}

	public function import(Request $request)
	{
		$request->validate([
			'file' => 'required|array',
			'file.*' => 'required|mimes:xlsx,xls'
		]);

		$skippedCodes = [];

		foreach ($request->file('file') as $uploadedFile) {
			$orders = new OrderImport();

			try {
				Excel::import($orders, $uploadedFile);

				if (!empty($orders->skipped)) {
					$skippedCodes = array_merge($skippedCodes, $orders->skipped);
				}

			} catch (\Exception $e) {
				return redirect()->route('orders.index')->with('error', 'Import failed: ' . $e->getMessage());
			}
		}

		if (!empty($skippedCodes)) {
			return redirect()->route('orders.index')->with(
				'error',
				'Imported with some skipped codes: ' . implode(', ', $skippedCodes)
			);
		}

		return redirect()->route('orders.index')->with('status', 'All files imported successfully!');
	}

	public function export(Request $request)
	{
		$mode = $request->input('mode');
		$ids = $request->input('order_ids');

		if ($mode === 'all') {
			$query = Order::query();

			// 🔍 Áp dụng lại các filter
			if ($request->filled('search')) {
				$search = $request->search;
				$query->where(function ($q) use ($search) {
					$q->where('order_id', 'like', '%' . $search . '%')
						->orWhere('sku', 'like', '%' . $search . '%')
						->orWhere('shop_name', 'like', '%' . $search . '%');
				});
			}

			if ($request->filled('user_id')) {
				$query->where('user_id', $request->user_id);
			}

			if ($request->filled('shop_name')) {
				$query->where('shop_name', $request->shop_name);
			}

			if ($request->filled('date_from')) {
				$query->whereDate('created_at', '>=', $request->date_from);
			}

			if ($request->filled('date_to')) {
				$query->whereDate('created_at', '<=', $request->date_to);
			}

			$orders = $query->get();
		} elseif ($mode === 'current' && is_array($ids)) {
			$orders = Order::whereIn('id', $ids)->get();
		} elseif (is_array($ids)) {
			$orders = Order::whereIn('id', $ids)->get();
		} else {
			return back()->with('error', 'No data to export.');
		}

		return Excel::download(new OrdersExport($orders), 'orders-' . now()->format('Ymd_His') . '.xlsx');
	}

	public function importFulfillFee(Request $request)
	{
		$request->validate([
			'file' => 'required|file|mimes:xlsx,xls',
		]);

		try {
			$import = new OrderFulfillFeeImport;
			Excel::import($import, $request->file('file'));

			$skippedExtraIds = implode(', ', $import->skipped);

			$message = '✅ Fulfill Fee updated for <strong>' . count($import->updated) . '</strong> orders.<br>' .
				'⚠️ Skipped <strong>' . count($import->skipped) . '</strong> rows: ' . e($skippedExtraIds);

			return redirect()->route('orders.index')->with('status', $message);

		} catch (\Exception $e) {
			return redirect()->route('orders.index')->with('error', $e->getMessage());
		}
	}

	public function downloadSample()
	{
		$path = public_path('sample_excel/sample_fulfill_fee.xlsx');

		return response()->download($path, 'sample_fulfill_fee.xlsx');
	}

	protected function isDuplicateOrder($extraId, $sku, $orderId, $ignoreId = null): bool
	{
		$query = Order::whereRaw('LOWER(extra_id) = ?', [strtolower($extraId)])
			->whereRaw('LOWER(sku) = ?', [strtolower($sku)])
			->whereRaw('LOWER(order_id) = ?', [strtolower($orderId)]);

		if ($ignoreId) {
			$query->where('id', '!=', $ignoreId);
		}

		return $query->exists();
	}
}
