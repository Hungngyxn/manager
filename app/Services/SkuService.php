<?php

namespace App\Services;

use App\Http\Controllers\ReportController;
use App\Models\Order;
use App\Models\Sku;
use App\Models\SkuOrder;

class SkuService
{
    public function updateOrdersBySku(Sku $sku, string $scope = 'all'): void
    {
        $skuName = strtolower($sku->sku);

        // 🟢 Tìm các đơn hàng có SKU đúng
        $query = Order::where('sku', $skuName);

        if ($scope === 'after_update') {
            $query->where('created_at', '>=', $sku->updated_at);
        }

        // ✅ Cập nhật các đơn đúng
        foreach ($query->get() as $order) {
            $service = new OrderService($sku, $order->quantity, $order->total, $order->fulfill_fee);
            $calculated = $service->calculate();

            $order->update([
                'cost' => $calculated['cost'],
                'profit' => $calculated['profit'],
                'bonus' => $calculated['bonus'],
                'total' => $calculated['total'],

            ]);
        }

        // 🔁 Tìm các đơn có SKU lỗi "not enough stock" hoặc "wrong sku"
        $wrongSkuOrders = Order::where(function ($q) use ($skuName) {
            $q->where('sku', 'like', "$skuName wrong sku")
                ->orWhere('sku', 'like', "$skuName not enough stock");
        })->get();

        foreach ($wrongSkuOrders as $order) {
            // Nếu tồn kho đã đủ thì cập nhật
            if ($sku->quantity >= $order->quantity) {
                $sku->decrement('quantity', $order->quantity);

                $service = new OrderService($sku, $order->quantity, $order->total, $order->fulfill_fee);
                $calculated = $service->calculate();

                $order->update([
                    'sku' => $skuName,
                    'cost' => $calculated['cost'],
                    'profit' => $calculated['profit'],
                    'bonus' => $calculated['bonus'],
                    'total' => $calculated['total'],

                ]);

                // ✅ Gọi cập nhật report
                ReportController::aggregateForDate($order->user_id, $order->created_at->toDateString());
            }
        }
    }

    public function parseSkuWithSkuOrder(string $rawSku, int $quantity): array
    {
        $rawSku = strtolower(trim($rawSku));

        $skuOrder = SkuOrder::whereRaw('LOWER(warehouse_name) = ?', [$rawSku])->first();

        if ($skuOrder) {
            $skuFromOrder = strtolower(trim($skuOrder->sku));
            return [
                'sku' => $skuFromOrder ?: $rawSku,
                'quantity' => $quantity * max((int) $skuOrder->quantity_per_pack, 1),
            ];
        }

        $skuExists = Sku::whereRaw('LOWER(sku) = ?', [$rawSku])->exists();
        return [
            'sku' => $skuExists ? $rawSku : $rawSku,
            'quantity' => $quantity,
        ];
    }
}
