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

class ShopUSController extends Controller
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
					$q->select('shop_code')->from('seller_has_shop')->whereNotNull('user_id');
				});
			} else {
				$query->whereIn('shop_code', $this->ownedShopCodes($request->user_id));
			}
		}

		// All Shops: lọc theo shop_us.shop_code (value dropdown là shop_code thật)
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
				->whereIn('shop_code', ShopUs::select('shop_code')->distinct())
				->distinct()
				->pluck('user_id');
			$sellers = User::whereIn('id', $sellerIds)->select('id', 'name')->orderBy('name')->get();
		}

		// Dropdown shops (giới hạn theo quyền)
		$shopsQuery = ShopUs::query()->whereNotNull('shop_code');
		if (!$isAdmin) {
			$shopsQuery->whereIn('shop_code', $this->ownedShopCodes($user->id));
		}
		$shopCodes = $shopsQuery->select('shop_code')->distinct()->pluck('shop_code');

		// shop_us.shop_code là shop_code thật -> map sang shop_name (seller_has_shop) để hiển thị.
		$nameByCode = SellerHasShop::whereIn('shop_code', $shopCodes)->pluck('shop_name', 'shop_code');
		$shops = $shopCodes->map(fn($code) => [
			'value' => $code,                       // lọc theo shop_us.shop_code (=shop_code thật)
			'label' => $nameByCode[$code] ?? $code, // hiển thị shop_name, fallback chính code
		]);

		return view('pages.order.index', compact('orders', 'ordercount', 'sellers', 'shops', 'isAdmin'));
	}

	/**
	 * Subquery danh sách shop_code một user sở hữu (qua seller_has_shop).
	 */
	private function ownedShopCodes($userId)
	{
		return function ($q) use ($userId) {
			$q->select('shop_code')->from('seller_has_shop')->where('user_id', $userId);
		};
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
	 * Lưu thông tin "Print setup" (modal ở trang /orders).
	 * - Thông tin theo từng sản phẩm (product_type, color, size, print_position,
	 *   design_url, mockup_url, special_print, is_embroidered) -> gộp vào cột `products`.
	 * - Thông tin cấp đơn (shipment, printer, shipping_label_url) -> cột `print`.
	 * Khớp đơn theo shop_us.order_id (giá trị JS gửi lên từ data-order).
	 */
	public function savePrintInfo(Request $request, $orderId)
	{
		$order = ShopUs::where('order_id', $orderId)->firstOrFail();
		$this->authorizeOrderAccess($order);

		$validated = $request->validate([
			'shipment' => 'nullable|string|max:255',
			'printer' => 'required|string|max:255',
			'shipping_label_url' => 'required|url|max:2000',
			'products' => 'required|array',
			'products.*.product_type' => 'nullable|string|max:255',
			'products.*.color' => 'nullable|string|max:255',
			'products.*.size' => 'nullable|string|max:255',
			'products.*.variant_id' => 'nullable|integer',
			'products.*.note' => 'nullable|string|max:500',
			'products.*.design_url' => 'nullable|url|max:2000',
			'products.*.mockup_url' => 'nullable|url|max:2000',
			'products.*.print_position' => 'nullable|array',
			'products.*.print_position.*' => 'string|max:255',
			'products.*.special_print' => 'nullable',
			'products.*.is_embroidered' => 'nullable',
			'products.*.sku' => 'nullable|string|max:255',
			'products.*.quantity' => 'nullable',
		]);

		// 1) Gộp thông tin print theo từng sản phẩm vào cột products (giữ nguyên dữ liệu cũ).
		$existing = $order->products ?? []; // đã cast 'array'
		$incoming = $validated['products'] ?? [];

		$printKeys = ['product_type', 'color', 'size', 'variant_id', 'note', 'print_position', 'design_url', 'mockup_url'];

		foreach ($incoming as $index => $data) {
			if (!isset($existing[$index]) || !is_array($existing[$index])) {
				$existing[$index] = [];
			}

			foreach ($printKeys as $k) {
				if (array_key_exists($k, $data)) {
					$existing[$index][$k] = $data[$k];
				}
			}

			// Checkbox: có mặt trong request = bật.
			$existing[$index]['special_print'] = !empty($data['special_print']);
			$existing[$index]['is_embroidered'] = !empty($data['is_embroidered']);
		}

		// 2) Thông tin cấp đơn -> cột print.
		$order->products = array_values($existing);
		$order->print = [
			'shipment' => $validated['shipment'] ?? null,
			'printer' => $validated['printer'],
			'shipping_label_url' => $validated['shipping_label_url'],
		];
		$order->save();

		return redirect()->back()->with('success', 'Print info saved successfully.');
	}

	/**
	 * "Send to printer": gửi đơn tới nhà in (FlashShip mặc định) ở chế độ nền.
	 * - Bắt buộc đã lưu Print setup (cột print).
	 * - Chống bấm nhiều lần: nếu đang 'sending' thì từ chối.
	 */
	public function sendToPrinter(Request $request, $orderId)
	{
		$order = ShopUs::where('order_id', $orderId)->firstOrFail();
		$this->authorizeOrderAccess($order);

		if (empty($order->print)) {
			return redirect()->back()->with('error', 'Chưa cấu hình Print setup cho đơn này.');
		}

		if ($order->print_status === ShopUs::PRINT_SENDING) {
			return redirect()->back()->with('error', 'Đơn đang được gửi tới nhà in, vui lòng đợi.');
		}

		// Khoá trạng thái trước khi đẩy job để chặn double-submit.
		$order->update(['print_status' => ShopUs::PRINT_SENDING]);

		\App\Jobs\SendOrderToPrinterJob::dispatchAfterResponse($order->id);

		return redirect()->back()->with('status', 'Đang gửi đơn tới nhà in ở chế độ nền. Tải lại trang để xem trạng thái.');
	}

	/**
	 * Chặn IDOR: user thường chỉ thao tác trên đơn của shop mình sở hữu.
	 */
	private function authorizeOrderAccess(ShopUs $order): void
	{
		$user = auth()->user();
		$isAdmin = (int) optional($user->role)->is_super_user === 1;
		if ($isAdmin) {
			return;
		}

		$owns = SellerHasShop::where('user_id', $user->id)
			->where('shop_code', $order->shop_code)
			->exists();
		if (!$owns) {
			abort(403);
		}
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

						$label = null;

						try {
							$this->tiktok->handleOrderAndGetLabel($client, $orderId);

						} catch (\Throwable $ex) {
							Log::error($ex->getMessage());
							continue;
						}
						/* ───────── Gom SKU ───────── */
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

						ShopUS::updateOrCreate(
							['order_id' => $orderId],
							[
								'order_id' => $orderId,
								'shop_code' => trim((string) $shop->shop_code),
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
				->where('shop_code', $order->shop_code)
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
	public function runSyncPendingOrdersStatus()
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

				$pendingOrderIds = ShopUs::where('shop_code', $shop->shop_code)
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

								if ($newStatus === 'AWAITING_COLLECTION') {
									$packageId = $orderData['packages'][0]['id'] ?? null;
									$currentOrder = ShopUs::where('order_id', $orderId)->first();

									if ($packageId & empty($currentOrder->label_link)) {
										$label = $client->Fulfillment->getPackageShippingDocument(
											$packageId,
											'SHIPPING_LABEL',
											'A6'
										);

										if (!empty($orderData['tracking_number'])) {
											$updateData['tracking_number'] = $orderData['tracking_number'];
										}
										$newLabel = $this->localizeLabel($label['doc_url'], $orderId);
										$updateData['label_link'] = $newLabel;
									}

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

		return redirect()->back()->with('status', 'Đã cập nhật trạng thái các đơn hàng thành công.');
	}
}
