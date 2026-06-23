<?php

namespace App\Imports;

use App\Http\Controllers\ReportController;
use App\Models\ErrorLog;
use App\Models\Order;
use App\Models\SellerHasShop;
use App\Models\ShopMapping;
use App\Models\Sku;
use App\Models\SkuOrder;
use App\Services\OrderService;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class OrderImport implements ToCollection, WithHeadingRow, WithStartRow
{
    public $skipped = [];
    public $calculated = [];

    public function startRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        $affectedUsersAndDates = [];
        $hasValidOrders = false;

        try {
            // existing keys to avoid duplicate import
            $existingKeys = array_flip(
                Order::selectRaw("LOWER(CONCAT(order_id, '___', sku, '___', extra_id)) AS composite_key")
                    ->pluck('composite_key')
                    ->toArray()
            );

            // group rows by order_id+sku+extra_id
            $grouped = $rows->groupBy(function ($row) {
                $orderId = strtolower(trim($row['order_id'] ?? ''));
                $extraId = strtolower(trim($row['sku_id'] ?? ''));
                $rawSku = strtolower(trim($row['seller_sku'] ?? ''));

                $parsed = $this->parseSkuWithSkuOrder($rawSku, 1);
                $skuCode = strtolower(trim($parsed['sku'] ?? $rawSku));

                return "{$orderId}___{$skuCode}___{$extraId}";
            });

            foreach ($grouped as $key => $groupRows) {
                $normalizedKey = strtolower($key);

                if (isset($existingKeys[$normalizedKey])) {
                    $this->skipped[] = $key . ' (duplicate)';
                    continue;
                }

                $firstRow = $groupRows->first();

                // skip canceled/returned
                if (!empty($firstRow['cancelation_return_type'])) {
                    $this->skipped[] = ($firstRow['order_id'] ?? 'unknown') . ' - ' . ($firstRow['seller_sku'] ?? 'unknown') . ' (Canceled/Returned)';
                    continue;
                }

                [$orderId, $skuCode, $extraId] = explode('___', $normalizedKey);

                $shopNameRaw = $firstRow['warehouse_name'] ?? null;
                $shopName = ShopMapping::resolveShopName($shopNameRaw);
                $userId = SellerHasShop::whereRaw('LOWER(shop_name) = ?', [$shopName])->value('user_id') ?? "Unassigned";

                if (!$orderId || !$skuCode || !$shopName) {
                    continue;
                }

                // --- created_at parsing & check older than 3 days
                $createdAt = null;
                if (!empty($firstRow['created_time'])) {
                    try {
                        $createdAt = Carbon::createFromFormat('m/d/Y g:i:s A', $firstRow['created_time']);
                    } catch (\Exception $e) {
                        try {
                            $createdAt = Carbon::parse($firstRow['created_time']);
                        } catch (\Exception $e2) {
                            $createdAt = now();
                        }
                    }
                } else {
                    $createdAt = now();
                }

                // if ($createdAt->lt(now()->subHours(84))) {
                //     $this->logError($orderId, "Order date older than 3 days ({$createdAt->toDateString()})", $userId);
                // }

                // quantity (expand packs)
                $originalQty = $groupRows->sum(fn($r) => (int) ($r['quantity'] ?? 1));
                $parsed = $this->parseSkuWithSkuOrder($firstRow['seller_sku'] ?? '', $originalQty);
                $skuFinal = strtolower(trim($parsed['sku'] ?? ''));
                $quantity = $parsed['quantity'];

                if (empty($skuFinal) || $quantity <= 0) {
                    continue;
                }

                $this->calculated[] = $parsed;

                // find sku in DB
                $sku = Sku::whereRaw('LOWER(sku) = ?', [$skuFinal])->first();

                // --- check freeship trên toàn order_id
                $rowsForSameOrder = $rows->where('order_id', $firstRow['order_id']);
                $orderHasFreeship = $this->shouldApplyFreeship($rowsForSameOrder);
                // dd($orderHasFreeship);

                // compute total
                $total = 0.0;
                $checkTotal = 0.0;
                $fulfillFee = 0.0;

                foreach ($groupRows as $r) {
                    $rowSubtotal = floatval($r['sku_subtotal_before_discount'] ?? 0);
                    $rowDiscount = floatval($r['sku_seller_discount'] ?? 0);
                    $rowShipDisc = floatval($r['shipping_fee_seller_discount'] ?? 0);

                    // tính thật (luôn trừ shipdisc)
                    $total += ($rowSubtotal - $rowDiscount - $rowShipDisc);

                    // tính để check freeship (chỉ bỏ shipdisc khi freeship)
                    $rowShipDiscForCheck = $orderHasFreeship ? 0.0 : $rowShipDisc;
                    $checkTotal += ($rowSubtotal - $rowDiscount - $rowShipDiscForCheck);

                    $fulfillFee += floatval($r['fulfill_fee'] ?? 0);
                }

                // check priceRef vs checkTotal
                if ($sku && is_numeric($sku->price)) {
                    $priceRef = floatval($sku->price) * $parsed['price_ref_quantity'];

                    if ($priceRef - $checkTotal > 8 && $checkTotal > 0) {
                        $diffPct = $priceRef > 0 ? round((($priceRef - $checkTotal) / $priceRef) * 100, 2) : 100;
                        $this->logError(
                            $orderId,
                            "Total lower than SKU price by {$diffPct}% (checkTotal: {$checkTotal}, priceRef: {$priceRef})",
                            $userId
                        );
                    }
                } else {
                    if (!$sku) {
                        $this->logError($orderId, $skuFinal . " not found in skus table", $userId);
                    } else {
                        $this->logError($orderId, $skuFinal . " price invalid or missing", $userId);
                    }
                }

                // compute cost/profit/bonus
                $cost = $profit = $bonus = 0.0;
                if ($sku) {
                    try {
                        $service = new OrderService($sku, $quantity, $total, $fulfillFee);
                        $calculated = $service->calculate();

                        $cost = $calculated['cost'] ?? 0.0;
                        $profit = $calculated['profit'] ?? 0.0;
                        $bonus = $calculated['bonus'] ?? 0.0;
                        $total = $calculated['total'] ?? $total;
                    } catch (\Throwable $e) {
                        $this->skipped[] = "Calculation failed: " . $e->getMessage();
                    }
                }

                // save order
                $checkedShop = SellerHasShop::whereRaw('LOWER(shop_name) = ?', [$shopName])->value('shop_name');

                $order = new Order([
                    'extra_id' => $extraId,
                    'sku' => $skuFinal,
                    'shop_name' => $checkedShop ?? $shopName . ' - New Shop',
                    'quantity' => $quantity,
                    'cost' => round($cost, 2),
                    'profit' => round($profit, 2),
                    'bonus' => round($bonus, 2),
                    'total' => round($total, 2),
                    'order_id' => $orderId,
                    'user_id' => $userId,
                ]);

                $order->forceFill(['created_at' => $createdAt])->save();

                $hasValidOrders = true;

                if (is_numeric($userId)) {
                    $affectedUsersAndDates[] = [$userId, $createdAt->toDateString()];
                }
            }

            if ($hasValidOrders) {
                DB::commit();
                foreach ($affectedUsersAndDates as [$userId, $date]) {
                    ReportController::aggregateForDate($userId, $date);
                }
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }

    protected function parseSkuWithSkuOrder(string $rawSku, int $originalQuantity): array
    {
        $rawSku = strtolower(trim($rawSku));

        // Nếu SKU không chứa "pack" -> chỉ dùng SkuOrder mapping (nếu có)
        if (strpos($rawSku, 'pack') === false) {
            $skuOrder = SkuOrder::whereRaw('LOWER(warehouse_name) = ?', [$rawSku])->first();

            if ($skuOrder) {
                $skuFromOrder = strtolower(trim($skuOrder->sku));
                $mappedQuantity = $originalQuantity * max((int) $skuOrder->quantity_per_pack, 1);

                return [
                    'sku' => $skuFromOrder ?: $rawSku,
                    'quantity' => $mappedQuantity,            // dùng cho cost, profit
                    'price_ref_quantity' => $originalQuantity // dùng cho priceRef
                ];
            }

            return [
                'sku' => $rawSku,
                'quantity' => $originalQuantity,
                'price_ref_quantity' => $originalQuantity,
            ];
        }

        // Nếu SKU có "pack" -> so sánh 2 cách và chọn cost thấp nhất
        $results = [];

        // Cách 1: lấy trực tiếp từ bảng skus
        $sku1 = Sku::whereRaw('LOWER(sku) = ?', [$rawSku])->first();
        if ($sku1) {
            $results[] = [
                'sku' => $rawSku,
                'quantity' => $originalQuantity,
                'cost' => $sku1->cost * $originalQuantity,
                'price_ref_quantity' => $originalQuantity,
            ];
        }

        // Cách 2: mapping qua sku_orders
        $skuOrder = SkuOrder::whereRaw('LOWER(warehouse_name) = ?', [$rawSku])->first();
        if ($skuOrder) {
            $skuFromOrder = strtolower(trim($skuOrder->sku));
            $mappedQuantity = $originalQuantity * max((int) $skuOrder->quantity_per_pack, 1);

            $sku2 = Sku::whereRaw('LOWER(sku) = ?', [$skuFromOrder])->first();
            if ($sku2) {
                $results[] = [
                    'sku' => $skuFromOrder,
                    'quantity' => $mappedQuantity,
                    'cost' => $sku2->cost * $mappedQuantity,
                    'price_ref_quantity' => $mappedQuantity, // với pack thì price_ref cũng theo mapped
                ];
            }
        }

        if (!empty($results)) {
            usort($results, fn($a, $b) => $a['cost'] <=> $b['cost']);
            $best = $results[0];
            return [
                'sku' => $best['sku'],
                'quantity' => $best['quantity'],
                'price_ref_quantity' => $best['price_ref_quantity'],
            ];
        }

        return [
            'sku' => $rawSku,
            'quantity' => $originalQuantity,
            'price_ref_quantity' => $originalQuantity,
        ];
    }

    /**
     * Check freeship condition: 
     * - All SKUs in the order_id are freeshipping
     * - Total > 30
     */
    private function shouldApplyFreeship(Collection $rowsForSameOrder): bool
    {
        $orderTotal = $rowsForSameOrder->sum(function ($r) {
            return floatval($r['sku_subtotal_before_discount'] ?? 0)
                - floatval($r['sku_seller_discount'] ?? 0);
        });

        $allFreeship = $rowsForSameOrder->every(function ($r) {
            $skuCode = strtolower(trim($r['seller_sku'] ?? ''));
            $skuObj = Sku::whereRaw('LOWER(sku) = ?', [$skuCode])->first();
            // dd($skuObj);
            return $skuObj && $skuObj->freeshipping;
        });

        // dd($orderTotal, $allFreeship);

        return $allFreeship && $orderTotal > 30;
    }

    private function logError($orderId, $message, $userId)
    {
        try {
            ErrorLog::updateOrCreate([
                'order_id' => $orderId,
                'error_message' => $message,
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            $this->skipped[] = "Log failed: " . $message;
        }

        $this->skipped[] = $message;
    }
}
