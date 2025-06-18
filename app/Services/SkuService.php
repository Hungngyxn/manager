<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Sku;

class SkuService
{
    public function updateOrdersBySku(Sku $sku, string $scope = 'all'): void
    {
        $query = Order::where('sku', $sku->sku);

        if ($scope === 'after_update') {
            $query->where('created_at', '>=', $sku->updated_at);
        }

        $orders = $query->get();
        $bonus_pct = $sku->tierBonus->bonus ?? 0;

        foreach ($orders as $order) {
            $cost = $sku->cost * $order->quantity;
            $profit = $order->total - $cost - $order->fulfill_fee;
            $bonus = $profit * ($bonus_pct / 100);

            $order->cost = round($cost, 2);
            $order->profit = round($profit, 2);
            $order->bonus = round($bonus, 2);
            $order->save();
        }
    }
}
