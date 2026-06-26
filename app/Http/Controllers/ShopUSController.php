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
	 * Hiển thị danh sách đơn hàng ShopUS
	 */
	public function index(Request $request)
	{
		$query = ShopUs::query();

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
			$query->where('status', $request->seller);
		}

		if ($request->filled('date')) {
			$date = $request->date;
			$query->whereDate('created_at', $date);
		}

		$orders = $query->latest()->get();
		$ordercount = $orders->count();

		// lấy danh sách shop & seller để lọc
		$shops = ShopUs::distinct()->pluck('shop_code');

		return view('pages.shopus.shopus', compact('orders', 'ordercount', 'shops'));
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

	public function syncOrdersWithLabel()
	{
		ini_set('max_execution_time', 0);
		set_time_limit(0);

		$shops = SellerHasShop::where('team_id', 10)->get();

		foreach ($shops as $shop) {

			try {

				/* ───────── Access Token ───────── */
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

							if (!isset($items[$sku])) {
								$items[$sku] = [
									'product_name' => $item['product_name'] ?? '',
									'sku' => $sku,
									'product_image' => $item['sku_image'] ?? '',
									'quantity' => $qty,
									'price' => $item['original_price'] ?? 0,
									'total_price' => $item['sale_price'] ?? 0,
								];
							} else {
								$items[$sku]['quantity'] += $qty;
							}
						}
						$items = array_values($items);

						/* ───────── Địa chỉ khách ───────── */
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
								'shop_code' => $shop->shop_code,
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
				continue; // 👉 lỗi shop thì sang shop tiếp
			}
		}

		return redirect()->back();
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

	public function parseSku(string $rawSku, int $originalQuantity): array
	{
		$rawSku = (trim($rawSku));

		// Nếu SKU không chứa "pack" -> chỉ dùng SkuOrder mapping (nếu có)
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

		// Nếu SKU có "pack" -> so sánh 2 cách và chọn cost thấp nhất
		$results = [];

		// Cách 2: mapping qua sku_orders
		$skuOrder = SkuOrder::whereRaw('LOWER(warehouse_name) = ?', [$rawSku])->first();
		if ($skuOrder) {
			$skuFromOrder = trim($skuOrder->asin);
			$mappedQuantity = $originalQuantity * max((int) $skuOrder->quantity_per_pack, 1);

			// $sku = Sku::whereRaw('LOWER(sku) = ?', [$skuFromOrder])->first();
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

	public function syncPendingOrdersStatus()
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

									if ($packageId) {
										$label = $client->Fulfillment->getPackageShippingDocument(
											$packageId,
											'SHIPPING_LABEL',
											'A6'
										);

										if (!empty($label['doc_url'])) {
											$updateData['label_link'] = $label['doc_url'];
										}
									}

									if (!empty($orderData['tracking_number'])) {
										$updateData['tracking_number'] = $orderData['tracking_number'];
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
