<?php

namespace App\Imports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OrderFulfillFeeImport implements ToCollection, WithHeadingRow
{
    public $updated = [];
    public $skipped = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $orderId = trim($row['order_number'] ?? '');
            $productName = trim($row['ten_san_pham'] ?? '');
            $fulfillFeeRaw = $row['tien_dich_vu'] ?? null;

            if (!$orderId || !$productName) {
                $this->skipped[] = $orderId;
                continue;
            }

            $order = Order::where('order_id', $orderId)
                ->where('product_name', $productName)
                ->first();

            if ($order) {
                $order->fulfill_fee = $fulfillFeeRaw;
                $order->save();

                $this->updated[] = $orderId;
            } else {
                $this->skipped[] = $orderId;
            }
        }
    }
}
