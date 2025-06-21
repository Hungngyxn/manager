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
                return $row['order_id'] . '___' . $row['seller_sku'];
            });

            foreach ($grouped as $key => $groupRows) {
                $firstRow = $groupRows->first();

                if (!empty($firstRow['cancelation_return_type'])) {
                    $this->skipped[] = ($firstRow['order_id'] ?? 'unknown') . ' - ' . ($firstRow['seller_sku'] ?? 'unknown') . ' (Canceled/Returned)';
                    continue;
                }

                [$extraId, $rawSku] = explode('___', $key);
                $shopName = trim($firstRow['warehouse_name']) ?? null;
                $userId = SellerHasShop::where('shop_name', $shopName)->value('user_id') ?? "Chưa assign";

                if (!$extraId || !$rawSku || !$shopName) {
                    $this->skipped[] = $extraId . ' - ' . $rawSku;
                    continue;
                }

                $originalQty = $groupRows->sum(fn($row) => (int) ($row['quantity'] ?? 1));

                $parsed = $this->parseSkuWithPack($rawSku, $originalQty);
                $skuCode = $parsed['sku'];
                $quantity = $parsed['quantity'];

                if (Order::where('extra_id', $extraId)->where('sku', $skuCode)->exists()) {
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

                Order::create([
                    'extra_id' => $extraId,
                    'sku' => $skuCode,
                    'shop_name' => $checkedShop ?? $shopName . ' - Chưa được add',
                    'quantity' => $quantity,
                    'cost' => $cost,
                    'profit' => $profit,
                    'bonus' => $bonus,
                    'total' => $total,
                    'user_id' => $userId,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // 👇 Hàm xử lý SKU có pack
    protected function parseSkuWithPack(string $rawSku, int $originalQuantity): array
    {
        // Nếu SKU gốc tồn tại, không cần xử lý pack
        if (Sku::where('sku', $rawSku)->exists()) {
            return [
                'sku' => $rawSku,
                'quantity' => $originalQuantity,
            ];
        }

        // Tách số lượng từ pattern như Pack2, 2pack, pack_3, _pack2, v.v.
        if (preg_match('/(?:^|_)?(?:pack)?(\d+)(?:pack)?(?:_|$)/i', $rawSku, $matches)) {
            $packQty = (int) $matches[1];

            // Loại bỏ phần "pack" ra khỏi SKU
            $cleanSku = preg_replace('/(?:^|_)?(?:pack)?\d+(?:pack)?(?:_|$)/i', '_', $rawSku);
            $cleanSku = trim($cleanSku, '_');

            return [
                'sku' => $cleanSku,
                'quantity' => $originalQuantity * $packQty,
            ];
        }

        // Không match pattern → trả nguyên
        return [
            'sku' => $rawSku,
            'quantity' => $originalQuantity,
        ];
    }

}
