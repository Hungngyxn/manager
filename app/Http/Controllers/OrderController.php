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
use App\Services\TikTokService;
use DB;
use Http;
use Illuminate\Http\Request;
use App\Imports\OrderImport;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
	protected $tiktok;

	public function __construct(TikTokService $tiktok)
	{
		$this->middleware('auth');
		$this->tiktok = $tiktok;
	}

	public function sync()
	{
		DB::beginTransaction();

		try {
			$shops = SellerHasShop::with('seller', 'token')->get(); // đảm bảo có quan hệ

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
			return redirect()->route('orders.index')->with('status', 'Đồng bộ đơn hàng thành công!');
		} catch (\Exception $e) {
			DB::rollBack();
			return redirect()->route('orders.index')->with('error', 'Lỗi đồng bộ đơn hàng: ' . $e->getMessage());
		}
	}

	public function index(Request $request)
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
			// Admin: hiển thị filter người bán và shop
			$shopNames = SellerHasShop::pluck('shop_name')->unique();
			$sellers = User::select('id', 'name')->distinct('name')->get();
		}

		// Admin hoặc Manager được phép lọc thêm
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

		$totalCost = $query->sum('cost');
		$totalRevenue = $query->sum('total');
		$totalProfit = $query->sum('profit');
		$totalQuantity = $query->sum('quantity');
		$orderCount = $query->count();

		$orders = $query->orderBy('created_at', 'desc')
			->paginate($perPage)
			->appends($request->only([
				'search',
				'user_id',
				'shop_name',
				'date_start',
				'date_end'
			]));

		return view('pages.order.index', compact(
			'orders',
			'sellers',
			'shopNames',
			'totalCost',
			'totalRevenue',
			'totalProfit',
			'totalQuantity',
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
			'sku' => 'required|string|max:255',
			'shop_name' => 'required|string|max:255',
			'quantity' => 'required|numeric|min:0',
			'total' => 'required|numeric|min:0',
			'user_id' => 'required|exists:users,id',
		]);

		DB::beginTransaction();

		try {
			$sku = Sku::where('sku', $validated['sku'])->firstOrFail();
			if ($sku->quantity < $validated['quantity']) {
				return redirect()->back()->with('error', 'Số lượng kho không đủ để tạo đơn hàng với ' . $sku->sku);
			}

			$service = new OrderService($sku, $validated['quantity'], $validated['total']);
			$calc = $service->calculate();

			$order = new Order(array_merge($validated, $calc));
			$order->save();

			$sku->decrement('quantity', $validated['quantity']);

			DB::commit();

			return redirect()->route('orders.index')->with('status', 'Order created successfully!');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->back()->with('error', 'Lỗi khi tạo đơn hàng: ' . $validated['extra_id']);
		}
	}

	public function edit(Order $order)
	{
		return view('pages.order.edit', compact('order'));
	}

	public function update(Request $request, Order $order)
	{
		$validated = $request->validate([
			'extra_id' => 'required|string|max:255',
			'sku' => 'required|string|max:100',
			'quantity' => 'required|numeric|min:0',
			'total' => 'required|numeric|min:0',
			'fulfill_fee' => 'numeric|min:0'
		]);

		DB::beginTransaction();

		try {
			$sku = Sku::where('sku', $validated['sku'])->firstOrFail();
			$service = new OrderService($sku, $validated['quantity'], $validated['total'], $validated['fulfill_fee']);
			$calc = $service->calculate();

			$order->sku = $validated['sku'];
			$order->quantity = $validated['quantity'];
			$order->total = $validated['total'];
			$order->cost = $calc['cost'];
			$order->profit = $calc['profit'];
			$order->bonus = $calc['bonus'];

			$order->save();

			DB::commit();

			return redirect()->back()->with('status', 'Order ' . $validated['extra_id'] . ' updated successfully!');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->back()->with('error', 'Lỗi khi cập nhật đơn hàng: ' . $validated['extra_id']);
		}
	}

	public function destroy(Order $order)
	{
		DB::beginTransaction();

		try {
			$order->delete();

			DB::commit();

			return redirect()->back()->with('status', 'Đơn hàng đã được xóa.');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->back()->with('error', 'Lỗi khi xóa đơn hàng: ' . $e->getMessage());
		}
	}

	public function delete(Request $request)
	{
		$orderIds = $request->input('order_ids');

		if (!$orderIds || !is_array($orderIds)) {
			return redirect()->back()->with('error', 'Không có đơn hàng nào được chọn.');
		}

		try {
			DB::beginTransaction();

			$orders = Order::whereIn('id', $orderIds)->get();
			Order::whereIn('id', $orderIds)->delete();

			DB::commit();

			return redirect()->route('orders.index')->with('status', 'Xóa đơn hàng thành công.');
		} catch (\Exception $e) {
			DB::rollBack();
			
			return redirect()->back()->with('error', 'Đã xảy ra lỗi khi xóa: ' . $e->getMessage());
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
				return redirect()->route('orders.index')->with('error', 'Import thất bại: ' . $e->getMessage());
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
			$orders = Order::all();
		} elseif ($mode === 'current' && is_array($ids)) {
			$orders = Order::whereIn('id', $ids)->get();
		} elseif (is_array($ids)) {
			$orders = Order::whereIn('id', $ids)->get();
		} else {
			return back()->with('error', 'Không có dữ liệu để export.');
		}

		return Excel::download(new OrdersExport($orders), 'orders-' . time() . '.xlsx');
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

			$message = '✅ Đã cập nhật Fulfill Fee cho <strong>' . count($import->updated) . '</strong> đơn hàng.<br>' .
				'⚠️ Bỏ qua <strong>' . count($import->skipped) . '</strong> dòng: ' . e($skippedExtraIds);

			return redirect()->route('orders.index')->with('status', $message);

		} catch (\Exception $e) {
			return redirect()->route('orders.index')->with('error', $e->getMessage());
		}

	}
}
