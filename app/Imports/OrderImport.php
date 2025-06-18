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

                [$extraId, $skuCode] = explode('___', $key);
                $shopName = trim($firstRow['warehouse_name']) ?? null;
                $userId = SellerHasShop::where('shop_name', $shopName)->get()->first()->user_id ?? auth()->user()->id;

                $sku = null;
                $cost = 0;
                $profit = 0;
                $bonus = 0;

                if (!$extraId || !$skuCode || !$shopName || Order::where('extra_id', $extraId)->where('sku', $skuCode)->exists()) {
                    $this->skipped[] = $extraId . ' - ' . $skuCode;
                    continue;
                }

                $quantity = $groupRows->sum(function ($row) {
                    return (int) ($row['quantity'] ?? 1);
                });

                $total = $groupRows->sum(function ($row) {
                    $calTotal = floatval($row['sku_subtotal_before_discount'] - $row['sku_seller_discount'] - $row['shipping_fee_seller_discount']);
                    return $calTotal;
                });

                $sku = Sku::where('sku', $skuCode)->first();

                if ($sku) {
                    $sku->decrement('quantity', $quantity);

                    $service = new OrderService($sku, $quantity, $total);
                    $calculated = $service->calculate();
                    $cost = $calculated['cost'];
                    $profit = $calculated['profit'];
                    $bonus = $calculated['bonus'];
                }

                $checkedShop = optional(SellerHasShop::where('shop_name', $shopName)->first())->shop_name;

                Order::create([
                    'extra_id' => $extraId,
                    'sku' => $skuCode,
                    'shop_name' => $checkedShop ?? $shopName . ' - Chưa được add',
                    'quantity' => $quantity,
                    'cost' => $cost ?? 0,
                    'profit' => $profit ?? 0,
                    'bonus' => $bonus ?? 0,
                    'total' => $total,
                    'user_id' => $userId ?? 0,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
