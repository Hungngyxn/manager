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
            $extraId = trim($row['extra_id'] ?? '');
            $sku = trim($row['sku'] ?? '');
            $fulfillFeeRaw = $row['fulfill_fee'] ?? null;

            // Check missing data
            if (empty($extraId) || empty($sku)) {
                $this->skipped[] = "[Missing Extra ID or SKU]";
                continue;
            }

            // Convert fulfill_fee to float (handle comma)
            $fulfillFee = is_numeric(str_replace(',', '.', $fulfillFeeRaw))
                ? floatval(str_replace(',', '.', $fulfillFeeRaw))
                : null;

            if ($fulfillFee === null) {
                $this->skipped[] = "$extraId / $sku - Invalid Fulfill Fee";
                continue;
            }

            // Find order by extra_id + sku
            $order = Order::where('extra_id', $extraId)
                ->where('sku', $sku)
                ->first();

            if ($order) {
                $order->fulfill_fee = $fulfillFee;

                // Recalculate profit
                $order->profit = $order->total - $order->cost - $fulfillFee;

                $order->save();
                $this->updated[] = "$extraId / $sku";
            } else {
                $this->skipped[] = "$extraId / $sku - Order not found";
            }
        }
    }
}
