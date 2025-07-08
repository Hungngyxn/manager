<?php

namespace App\Imports;

use App\Http\Controllers\ReportController;
use App\Models\Order;
use App\Models\SellerHasShop;
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
            $existingKeys = array_flip(
                Order::selectRaw("LOWER(CONCAT(order_id, '___', sku, '___', extra_id)) AS composite_key")
                    ->pluck('composite_key')
                    ->toArray()
            );

            $grouped = $rows->groupBy(function ($row) {
                $orderId = strtolower(trim($row['order_id']));
                $extraId = strtolower(trim($row['sku_id']));
                $rawSku = strtolower(trim($row['seller_sku']));

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

                if (!empty($firstRow['cancelation_return_type'])) {
                    $this->skipped[] = ($firstRow['order_id'] ?? 'unknown') . ' - ' . ($firstRow['seller_sku'] ?? 'unknown') . ' (Canceled/Returned)';
                    continue;
                }

                [$orderId, $skuCode, $extraId] = explode('___', $normalizedKey);

                $shopName = strtolower(trim($firstRow['warehouse_name'])) ?? null;
                $userId = SellerHasShop::whereRaw('LOWER(shop_name) = ?', [$shopName])->value('user_id') ?? "Unassigned";

                if (!$orderId || !$skuCode || !$shopName) {
                    $this->skipped[] = $orderId . ' - ' . $skuCode . ' (missing data)';
                    continue;
                }

                $createdTimeRaw = $firstRow['created_time'] ?? null;
                try {
                    $createdAt = $createdTimeRaw
                        ? Carbon::createFromFormat('m/d/Y g:i:s A', $createdTimeRaw)
                        : now();
                } catch (\Exception $e) {
                    $this->skipped[] = "$orderId - $skuCode (invalid created_time)";
                    continue;
                }

                $originalQty = $groupRows->sum(fn($row) => (int) ($row['quantity'] ?? 1));

                $parsed = $this->parseSkuWithSkuOrder($firstRow['seller_sku'], $originalQty);
                $skuFinal = strtolower(trim($parsed['sku']));
                $quantity = $parsed['quantity'];

                if (empty($skuFinal) || $quantity <= 0) {
                    $this->skipped[] = "$orderId - $skuCode (empty SKU or invalid quantity)";
                    continue;
                }

                $this->calculated[] = $parsed;

                $total = $groupRows->sum(function ($row) {
                    return floatval(
                        ($row['sku_subtotal_before_discount'] ?? 0)
                        - ($row['sku_seller_discount'] ?? 0)
                        - ($row['shipping_fee_seller_discount'] ?? 0)
                    );
                });

                $sku = Sku::whereRaw('LOWER(sku) = ?', [$skuFinal])->first();
                $cost = $profit = $bonus = 0;

                if ($sku) {
                    if ($sku->quantity >= $quantity) {
                        $sku->decrement('quantity', $quantity);

                        $service = new OrderService($sku, $quantity, $total);
                        $calculated = $service->calculate();
                        $cost = $calculated['cost'];
                        $profit = $calculated['profit'];
                        $bonus = $calculated['bonus'];
                    } else {
                        $skuFinal .= ' not enough stock';
                        $this->skipped[] = "$orderId - $skuFinal (Not enough stock. Available: {$sku->quantity}, Required: {$quantity})";
                    }
                } else {
                    $skuFinal .= ' wrong sku';
                    $this->skipped[] = "$orderId - $skuFinal (SKU not found in stock)";
                }

                $checkedShop = SellerHasShop::whereRaw('LOWER(shop_name) = ?', [$shopName])->value('shop_name');

                $order = new Order([
                    'extra_id' => $extraId,
                    'sku' => $skuFinal,
                    'shop_name' => $checkedShop ?? $shopName . ' - New Shop',
                    'quantity' => $quantity,
                    'cost' => $cost,
                    'profit' => $profit,
                    'bonus' => $bonus,
                    'total' => $total,
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
            $this->skipped[] = 'File import failed: ' . $e->getMessage();
        }
    }

    protected function parseSkuWithSkuOrder(string $rawSku, int $originalQuantity): array
    {
        $rawSku = strtolower(trim($rawSku));

        $skuOrder = SkuOrder::whereRaw('LOWER(warehouse_name) = ?', [$rawSku])->first();

        if ($skuOrder) {
            $skuFromOrder = strtolower(trim($skuOrder->sku));

            return [
                'sku' => $skuFromOrder ?: $rawSku,
                'quantity' => $originalQuantity * max((int) $skuOrder->quantity_per_pack, 1),
            ];
        }

        $skuExists = Sku::whereRaw('LOWER(sku) = ?', [$rawSku])->exists();

        if ($skuExists) {
            return [
                'sku' => $rawSku,
                'quantity' => $originalQuantity,
            ];
        }

        return [
            'sku' => $rawSku,
            'quantity' => $originalQuantity,
        ];
    }
}
