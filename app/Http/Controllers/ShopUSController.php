<?php

namespace App\Http\Controllers;

use App\Exports\ShopUsExport;
use App\Models\ApiToken;
use App\Models\SellerHasShop;
use App\Models\ShopUs;
use App\Models\Sku;
use App\Models\SkuOrder;
use App\Models\TiktokToken;
use App\Models\User;
use App\Services\SaigonApiService;
use App\Services\TikTokService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Log;
use Maatwebsite\Excel\Facades\Excel;

class ShopUsController extends Controller
{
	protected $tiktok;

	public function __construct(TikTokService $tiktok)
	{
		$this->tiktok = $tiktok;
	}
	/**
	 * Danh sách đơn ShopUS.
	 */
	public function index(Request $request)
	{
		$user = auth()->user();
		$isAdmin = (int) optional($user->role)->is_super_user === 1;
		$perPage = $request->get('perPage', 10);

		$query = ShopUs::query();

		// Phân quyền: admin xem tất cả; user thường chỉ xem shop mình sở hữu.
		if (!$isAdmin) {
			$query->whereIn('shop_code', $this->ownedShopCodes($user->id));
		}

		if ($request->filled('search')) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('order_id', 'like', "%{$search}%")
					->orWhere('customer_name', 'like', "%{$search}%")
					->orWhere('shop_code', 'like', "%{$search}%")
					->orWhere('tracking_number', 'like', "%{$search}%");
			});
		}

		if ($request->filled('shop')) {
			$query->where('shop_code', $request->shop);
		}

		if ($request->filled('status')) {
			$query->where('status', $request->status);
		}

		if ($request->filled('date')) {
			$query->whereDate('created_at', $request->date);
		}

		$ordercount = (clone $query)->count();

		$orders = $query->latest()
			->paginate($perPage)
			->appends($request->only(['search', 'shop', 'status', 'date', 'perPage']));

		// Danh sách shop để lọc (giới hạn theo quyền)
		$shopsQuery = ShopUs::query();
		if (!$isAdmin) {
			$shopsQuery->whereIn('shop_code', $this->ownedShopCodes($user->id));
		}
		$shops = $shopsQuery->select('shop_code')->distinct()->pluck('shop_code');

		return view('pages.shopus.shopus', compact('orders', 'ordercount', 'shops'));
	}

	/**
	 * Subquery danh sách shop_name một user sở hữu (qua seller_has_shop).
	 */
	private function ownedShopCodes($userId)
	{
		return function ($q) use ($userId) {
			$q->select('shop_name')->from('seller_has_shop')->where('user_id', $userId);
		};
	}

	/**
	 * Trang orders: filter (seller / shop / ngày / search) + bảng item ShopUS.
	 */
	public function board(Request $request)
	{
		$user = auth()->user();
		$isAdmin = (int) optional($user->role)->is_super_user === 1;
		$perPage = $request->get('perPage', 10);

		$query = ShopUs::query()->with('seller');

		// Phân quyền: user thường chỉ thấy đơn của shop mình sở hữu (join seller_has_shop)
		if (!$isAdmin) {
			$query->whereIn('shop_code', $this->ownedShopCodes($user->id));
		}

		// All Sellers (chỉ admin mới lọc được theo seller)
		if ($isAdmin && $request->filled('user_id')) {
			if ($request->user_id === 'Unassigned') {
				// Đơn của shop chưa được gán seller nào
				$query->whereNotIn('shop_code', function ($q) {
					$q->select('shop_name')->from('seller_has_shop')->whereNotNull('user_id');
				});
			} else {
				$query->whereIn('shop_code', $this->ownedShopCodes($request->user_id));
			}
		}

		// All Shops: shop_us.shop_code lưu shop_name
		if ($request->filled('shop_name')) {
			$query->where('shop_code', $request->shop_name);
		}

		if ($request->filled('date_start')) {
			$query->whereDate('created_at', '>=', $request->date_start);
		}
		if ($request->filled('date_end')) {
			$query->whereDate('created_at', '<=', $request->date_end);
		}

		if ($request->filled('search')) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('order_id', 'like', "%{$search}%")
					->orWhere('customer_name', 'like', "%{$search}%")
					->orWhere('shop_code', 'like', "%{$search}%")
					->orWhere('tracking_number', 'like', "%{$search}%");
			});
		}

		$ordercount = (clone $query)->count();

		$orders = $query->latest()
			->paginate($perPage)
			->appends($request->only(['user_id', 'shop_name', 'date_start', 'date_end', 'search', 'perPage']));

		// Dropdown sellers (chỉ admin): seller sở hữu shop có đơn ShopUS
		$sellers = collect();
		if ($isAdmin) {
			$sellerIds = SellerHasShop::whereNotNull('user_id')
				->whereIn('shop_name', ShopUs::select('shop_code')->distinct())
				->distinct()
				->pluck('user_id');
			$sellers = User::whereIn('id', $sellerIds)->select('id', 'name')->orderBy('name')->get();
		}

		// Dropdown shops (giới hạn theo quyền)
		$shopsQuery = ShopUs::query()->whereNotNull('shop_code');
		if (!$isAdmin) {
			$shopsQuery->whereIn('shop_code', $this->ownedShopCodes($user->id));
		}
		$shopNames = $shopsQuery->select('shop_code')->distinct()->pluck('shop_code');

		return view('pages.shopus.board', compact('orders', 'ordercount', 'sellers', 'shopNames', 'isAdmin'));
	}

	/**
	 * Cập nhật Tracking Number và Label Link
	 */
	public function update(Request $request)
	{
		$validated = $request->validate([
			'id' => 'required|exists:shop_us,id',
			'tracking_number' => 'nullable|string|max:255',
			'label_link' => 'nullable|string|max:500',
		]);

		$order = ShopUs::findOrFail($validated['id']);
		$order->update([
			'tracking_number' => $validated['tracking_number'] ?? null,
			'label_link' => $validated['label_link'] ?? null,
		]);

		return redirect()->back()->with('success', 'ShopUS order updated successfully.');
	}

	/**
	 * Đẩy việc đồng bộ chạy nền sau response để không treo trình duyệt.
	 */
	public function syncOrdersWithLabel()
	{
		\App\Jobs\SyncShopUsLabelsJob::dispatchAfterResponse();

		return redirect()->back()->with('status', 'Đang tạo label ở chế độ nền. Vui lòng tải lại trang sau ít phút.');
	}

	/**
	 * Phần xử lý nặng thực sự: gọi từ Job (web) hoặc trực tiếp từ cron (CLI).
	 */
	public function runSyncOrdersWithLabel(): void
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);

		$shops = SellerHasShop::where('team_id', 10)->get();

		$pst1 = new \DateTime('now', new \DateTimeZone('America/Los_Angeles'));
		$pst = (clone $pst1)->modify('-1 day');

		$startOfDaySLA = (clone $pst)->setTime(2, 0, 0)->getTimestamp();
		$endOfDaySLA = (clone $pst1)->setTime(6, 0, 0)->getTimestamp();

		foreach ($shops as $shop) {

			try {

				// Access token
				$token = $this->tiktok->getAccessToken($shop);

				if (!$token) {
					Log::warning("Không tìm thấy token cho shop: {$shop->shop_name}");
					continue;
				}

				$client = $this->tiktok->client();
				$client->setAccessToken($token);
				$client->setShopCipher($shop->shop_cipher);
					
				$orders = $this->tiktok->fetchOrderList($client);

				if (empty($orders)) {
					Log::info("Shop {$shop->shop_name} không có đơn nào.");
					continue;
				}

				foreach ($orders as $order) {
					try {
						$orderId = $order['id'];
						$slaTime = $order['rts_time'] ?? null;

						if (!$slaTime || $slaTime < $startOfDaySLA || $slaTime > $endOfDaySLA) {
							Log::info("Shop {$shop->shop_name} - Bỏ qua Order {$orderId} (label được tạo không phải hôm nay).");
							continue;
						}

						$packageId = $order['packages'][0]['id'] ?? null;
						if (!$packageId) {
							Log::warning("Order {$orderId} chưa có package_id.");
							continue;
						}

						$label = null;
						try {
							$label = $client->Fulfillment->getPackageShippingDocument(
								$packageId,
								'SHIPPING_LABEL',
								'A6'
							);
						} catch (\Throwable $ex) {
							Log::warning("Không lấy được label Order {$orderId}: " . $ex->getMessage());
						}

						// Gom SKU
						$items = [];
						foreach ($order['line_items'] as $item) {
							$basesku = $item['seller_sku'] ?? '';
							$baseqty = $item['quantity'] ?? 1;

							$parsed = $this->parseSku($basesku, $baseqty);

							$sku = $parsed['sku'];
							$qty = $parsed['quantity'];

							// Giá gốc & base cost: tra baseSku vào skus (fallback qua sku_orders).
							$baseInfo = $this->calcBaseCost($basesku);

							if (!isset($items[$sku])) {
								$items[$sku] = [
									'product_name' => $item['product_name'] ?? '',
									'sku' => $sku,
									'product_image' => $item['sku_image'] ?? '',
									'quantity' => $qty,
									'price' => $item['original_price'] ?? 0,
									'total_price' => $item['sale_price'] ?? 0,
									'base_cost' => $baseInfo['base_cost'] ?? null,
								];
							} else {
								$items[$sku]['quantity'] += $qty;
								if ($baseInfo !== null) {
									$items[$sku]['base_cost'] = ($items[$sku]['base_cost'] ?? 0) + $baseInfo['base_cost'];
								}
							}
						}
						$items = array_values($items);

						// Địa chỉ khách
						$recipient = $order['recipient_address'] ?? [];
						$district = collect($recipient['district_info'] ?? []);

						$country = $district->firstWhere('address_level', 'L0')['address_name'] ?? null;
						$state = $district->firstWhere('address_level', 'L1')['address_name'] ?? null;
						$city = $district->firstWhere('address_level', 'L3')['address_name'] ?? null;

						$existing = ShopUS::where('order_id', $orderId)->first();

						$trackingNumber = $label['tracking_number'] ?? ($existing->tracking_number ?? null);
						$labelLink = $label['doc_url'] ?? ($existing->label_link ?? null);

						// Tải label (PDF) về local để tránh link TikTok hết hạn
						if (!empty($label['doc_url'])) {
							$localLabel = $this->localizeLabel($label['doc_url'], $orderId);
							if ($localLabel) {
								$labelLink = $localLabel;
							}
						}

						ShopUS::updateOrCreate(
							['order_id' => $orderId],
							[
								'order_id' => $orderId,
								'shop_code' => $shop->shop_name,
								'customer_name' => $recipient['name'] ?? '',
								'customer_phone' => $recipient['phone_number'] ?? '',
								'customer_address' => $recipient['address_detail'] ?? '',
								'customer_country' => $country,
								'customer_state' => $state,
								'customer_city' => $city,
								'customer_postcode' => $recipient['postal_code'] ?? '',
								'status' => $order['status'] ?? '',
								'total_amount' => $items[0]['total_price'],
								'price' => $items[0]['price'],
								'products' => $items,
								'tracking_number' => $trackingNumber,
								'label_link' => $labelLink,
							]
						);

						Log::info("Shop {$shop->shop_name} - Đồng bộ Order {$orderId} thành công.");

					} catch (\Throwable $ex) {
						Log::error("Shop {$shop->shop_name} - Lỗi xử lý Order {$order['id']}: " . $ex->getMessage());
						continue;
					}
				}

			} catch (\Throwable $e) {
				Log::error('Lỗi syncOrdersWithLabel', [
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				]);
				continue; // lỗi shop này thì sang shop kế tiếp
			}
		}
	}

	public function exportSelected(Request $request)
	{
		$ids = $request->input('ids', []);

		if (empty($ids)) {
			return back()->with('error', 'Vui lòng chọn ít nhất một đơn hàng để xuất.');
		}

		$orders = ShopUs::whereIn('order_id', $ids)->get();

		$fileName = 'orders_selected_' . now()->format('Ymd_His') . '.xlsx';

		return Excel::download(new ShopUsExport($orders), $fileName);
	}

	/**
	 * Tải label PDF từ TikTok về local để tránh link TikTok hết hạn.
	 * Lưu vào thư mục label_links/ ngay gốc project (ngang cấp public/, storage/)
	 * và trả về đường dẫn tương đối "label_links/{order_id}.pdf", hoặc null nếu thất bại.
	 */
	public function localizeLabel(?string $url, string $orderId): ?string
	{
		if (!$url) {
			return null;
		}

		// Đã là đường dẫn local (label_links/...) thì giữ nguyên, không tải lại
		if (!str_starts_with($url, 'http')) {
			return str_contains($url, 'label_links/') ? $url : null;
		}

		try {
			$response = Http::timeout(60)->get($url);

			if (!$response->successful()) {
				Log::warning("Tải label thất bại Order {$orderId}: HTTP {$response->status()}");
				return null;
			}

			// Tên file an toàn theo order_id; label TikTok là PDF
			$safeId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $orderId);
			$relativePath = "label_links/{$safeId}.pdf";
			$fullPath = base_path($relativePath);

			// Đảm bảo thư mục label_links/ ở gốc project tồn tại
			$dir = dirname($fullPath);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}

			file_put_contents($fullPath, $response->body());

			return $relativePath;
		} catch (\Throwable $e) {
			Log::warning("Lỗi tải label Order {$orderId}: " . $e->getMessage());
			return null;
		}
	}

	/**
	 * Phục vụ file label PDF nằm trong label_links/ ở gốc project (ngoài web docroot).
	 * - label_link là đường dẫn local "label_links/..." -> trả file PDF (xem inline).
	 * - label_link còn là link TikTok gốc -> redirect thẳng sang đó.
	 */
	public function downloadLabel($id)
	{
		$order = ShopUs::findOrFail($id);

		// Phân quyền: user thường chỉ tải được label của shop mình sở hữu (chống IDOR).
		$user = auth()->user();
		$isAdmin = (int) optional($user->role)->is_super_user === 1;
		if (!$isAdmin) {
			$owns = SellerHasShop::where('user_id', $user->id)
				->where('shop_name', $order->shop_code)
				->exists();
			if (!$owns) {
				abort(403);
			}
		}

		$labelLink = $order->label_link;

		if (!$labelLink) {
			abort(404);
		}

		// Chưa localize (vẫn là link ngoài) thì chuyển hướng thẳng
		if (str_starts_with($labelLink, 'http')) {
			return redirect()->away($labelLink);
		}

		if (!str_starts_with($labelLink, 'label_links/')) {
			abort(404);
		}

		$fullPath = base_path($labelLink);
		$real = realpath($fullPath);
		$baseDir = realpath(base_path('label_links'));

		// Chặn path traversal: file phải nằm trong label_links/
		if ($real === false || $baseDir === false || !str_starts_with($real, $baseDir)) {
			abort(404);
		}

		return response()->file($real, ['Content-Type' => 'application/pdf']);
	}

	public function parseSku(string $rawSku, int $originalQuantity): array
	{
		$rawSku = (trim($rawSku));

		// SKU không có "pack": chỉ dùng mapping sku_orders (nếu có)
		if (strpos($rawSku, 'pack') === false) {
			$skuOrder = SkuOrder::whereRaw('LOWER(warehouse_name) = ?', [$rawSku])->first();

			if ($skuOrder) {
				$skuFromOrder = (trim($skuOrder->asin));
				$mappedQuantity = $originalQuantity * max((int) $skuOrder->quantity_per_pack, 1);

				return [
					'sku' => $skuFromOrder ?: $rawSku,
					'quantity' => $mappedQuantity
				];
			}

			return [
				'sku' => $rawSku,
				'quantity' => $originalQuantity
			];
		}

		// SKU có "pack": mapping qua sku_orders
		$results = [];

		$skuOrder = SkuOrder::whereRaw('LOWER(warehouse_name) = ?', [$rawSku])->first();
		if ($skuOrder) {
			$skuFromOrder = trim($skuOrder->asin);
			$mappedQuantity = $originalQuantity * max((int) $skuOrder->quantity_per_pack, 1);

			if ($skuFromOrder) {
				$results[] = [
					'sku' => $skuFromOrder,
					'quantity' => $mappedQuantity,
				];
			}
		}

		if (!empty($results)) {
			$best = $results[0];
			return [
				'sku' => $best['sku'],
				'quantity' => $best['quantity'],
			];
		}

		return [
			'sku' => $rawSku,
			'quantity' => $originalQuantity,
		];
	}

	/**
	 * Lấy thông tin giá gốc của 1 item từ seller_sku (KHÔNG parse chuỗi).
	 * Trả về ['price' => ..., 'base_cost' => ...], hoặc null nếu không map được → view hiển thị N/A.
	 *
	 * 1) Tra thẳng baseSku vào bảng skus. Có dòng sku = baseSku → lấy luôn price & cost (quantity = 1).
	 * 2) Không có → tra baseSku vào sku_orders theo warehouse_name, copy sku + quantity_per_pack của dòng đó.
	 *    Cầm sku vừa copy tra lại vào skus, lấy price & cost rồi nhân cả hai với quantity_per_pack.
	 */
	public function calcBaseCost(string $rawSku): ?array
	{
		$rawSku = trim($rawSku);
		if ($rawSku === '') {
			return null;
		}

		// 1) Tra thẳng baseSku vào skus
		$skuRow = Sku::whereRaw('LOWER(sku) = ?', [strtolower($rawSku)])->first();
		if ($skuRow) {
			return [
				'price' => round((float) $skuRow->price, 2),
				'base_cost' => round((float) $skuRow->cost, 2),
			];
		}

		// 2) Không có → qua sku_orders (warehouse_name), copy sku + quantity, rồi quay lại skus
		$skuOrder = SkuOrder::whereRaw('LOWER(warehouse_name) = ?', [strtolower($rawSku)])->first();
		if (!$skuOrder) {
			return null;
		}

		$mappedSku = trim((string) $skuOrder->sku);
		$quantity = max((int) $skuOrder->quantity_per_pack, 1);
		if ($mappedSku === '') {
			return null;
		}

		$skuRow = Sku::whereRaw('LOWER(sku) = ?', [strtolower($mappedSku)])->first();
		if (!$skuRow) {
			return null;
		}

		return [
			'price' => round((float) $skuRow->price * $quantity, 2),
			'base_cost' => round((float) $skuRow->cost * $quantity, 2),
		];
	}

	/**
	 * Action web: đẩy việc cập nhật trạng thái chạy sau response để không treo trình duyệt.
	 */
	public function syncPendingOrdersStatus()
	{
		\App\Jobs\SyncShopUsPendingStatusJob::dispatchAfterResponse();

		return redirect()->back()->with('status', 'Đang cập nhật trạng thái đơn ở chế độ nền. Vui lòng tải lại trang sau ít phút.');
	}

	/**
	 * Phần xử lý nặng thực sự: gọi từ Job (web) hoặc trực tiếp từ CLI.
	 */
	public function runSyncPendingOrdersStatus(): void
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);

		$shops = SellerHasShop::where('team_id', 10)->get();

		foreach ($shops as $shop) {
			try {
				$token = $this->tiktok->getAccessToken($shop);
				if (!$token) {
					Log::warning("Không tìm thấy token cho shop: {$shop->shop_name}");
					continue;
				}

				$client = $this->tiktok->client();
				$client->setAccessToken($token);
				$client->setShopCipher($shop->shop_cipher);

				$pendingOrderIds = ShopUs::where('shop_code', $shop->shop_name)
					->whereNotIn('status', ['DELIVERED', 'CANCELLED', 'COMPLETED'])
					->pluck('order_id')
					->toArray();

				if (empty($pendingOrderIds)) {
					continue;
				}

				$orderChunks = array_chunk($pendingOrderIds, 50);

				foreach ($orderChunks as $chunk) {
					try {
						$ordersFromServer = $this->tiktok->fetchOrderDetails($client, $chunk);

						if (empty($ordersFromServer)) {
							continue;
						}

						foreach ($ordersFromServer as $orderData) {
							$orderId = $orderData['id'] ?? null;
							$newStatus = $orderData['status'] ?? null;

							if ($orderId && $newStatus) {
								$updateData = [
									'status' => $newStatus
								];

								if (!empty($orderData['tracking_number'])) {
									$updateData['tracking_number'] = $orderData['tracking_number'];
								}

								ShopUs::where('order_id', $orderId)->update($updateData);
							}
						}

						sleep(1);

					} catch (\Throwable $chunkEx) {
						Log::error("Lỗi chunk shop {$shop->shop_name}: " . $chunkEx->getMessage());
						continue;
					}
				}

			} catch (\Throwable $e) {
				Log::error("Lỗi tổng shop {$shop->shop_name}: " . $e->getMessage());
				continue;
			}
		}
	}

}
