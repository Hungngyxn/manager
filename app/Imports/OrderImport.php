<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\Sku;
use App\Models\SellerHasShop;
use App\Services\OrderService;
use DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Carbon\Carbon;

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

        try {
            $grouped = $rows->groupBy(function ($row) {
                return $row['order_id'] . '___' . $row['seller_sku'] . '___' . $row['sku_id'];
            });

            foreach ($grouped as $key => $groupRows) {
                $firstRow = $groupRows->first();

                if (!empty($firstRow['cancelation_return_type'])) {
                    $this->skipped[] = ($firstRow['order_id'] ?? 'unknown') . ' - ' . ($firstRow['seller_sku'] ?? 'unknown') . ' (Canceled/Returned)';
                    continue;
                }

                [$extraId, $rawSku, $orderId] = explode('___', $key);
                $shopName = trim($firstRow['warehouse_name']) ?? null;
                $userId = SellerHasShop::where('shop_name', $shopName)->value('user_id') ?? "Chưa assign";

                if (!$extraId || !$rawSku || !$shopName) {
                    $this->skipped[] = $extraId . ' - ' . $rawSku;
                    continue;
                }

                // 👉 Lấy Created Time từ Excel và parse
                $createdTimeRaw = $firstRow['created_time'] ?? null;
                try {
                    $createdAt = $createdTimeRaw
                        ? Carbon::createFromFormat('m/d/Y g:i:s A', $createdTimeRaw)
                        : now();
                } catch (\Exception $e) {
                    $this->skipped[] = "$extraId - $rawSku (invalid created_time)";
                    continue;
                }

                $originalQty = $groupRows->sum(fn($row) => (int) ($row['quantity'] ?? 1));
                $parsed = $this->parseSkuWithPack($rawSku, $originalQty);
                $this->calculated[] = $parsed;
                $skuCode = $parsed['sku'];
                $quantity = $parsed['quantity'];

                if (Order::where('extra_id', $extraId)->where('sku', $skuCode)->where('order_id', $orderId)->exists()) {
                    $this->skipped[] = $extraId . ' - ' . $skuCode;
                    continue;
                }

                $total = $groupRows->sum(function ($row) {
                    return floatval($row['sku_subtotal_before_discount'] - $row['sku_seller_discount'] - $row['shipping_fee_seller_discount']);
                });

                $sku = Sku::where('sku', $skuCode)->first();
                $cost = $profit = $bonus = 0;

                if ($sku) {
                    $sku->decrement('quantity', $quantity);
                    $service = new OrderService($sku, $quantity, $total);
                    $calculated = $service->calculate();
                    $cost = $calculated['cost'];
                    $profit = $calculated['profit'];
                    $bonus = $calculated['bonus'];
                }

                $checkedShop = SellerHasShop::where('shop_name', $shopName)->value('shop_name');

                $order = new Order([
                    'extra_id' => $extraId,
                    'sku' => $skuCode,
                    'shop_name' => $checkedShop ?? $shopName . ' - Chưa được add',
                    'quantity' => $quantity,
                    'cost' => $cost,
                    'profit' => $profit,
                    'bonus' => $bonus,
                    'total' => $total,
                    'order_id' => $orderId,
                    'user_id' => $userId,
                ]);

                $order->forceFill([
                    'created_at' => $createdAt,
                ])->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function parseSkuWithPack(string $rawSku, int $originalQuantity): array
    {
        // Nếu SKU gốc tồn tại → giữ nguyên
        if (Sku::where('sku', $rawSku)->exists()) {
            return [
                'sku' => $rawSku,
                'quantity' => $originalQuantity,
            ];
        }

        $packQty = 1;
        $cleanSku = $rawSku;

        // 👉 Xử lý "pack" ở đầu
        if (preg_match('/^(pack)?(\d+)(pack)?[_-]/i', $rawSku, $matches)) {
            $packQty = (int) $matches[2];
            $cleanSku = preg_replace('/^(pack)?\d+(pack)?[_-]/i', '', $rawSku);
        }

        // 👉 Xử lý "pack" ở cuối
        elseif (preg_match('/[_-](pack)?(\d+)(pack)?$/i', $rawSku, $matches)) {
            $packQty = (int) $matches[2];
            $cleanSku = preg_replace('/[_-](pack)?\d+(pack)?$/i', '', $rawSku);
        }

        return [
            'sku' => trim($cleanSku, '_- '),
            'quantity' => $originalQuantity * $packQty,
        ];
    }

}
