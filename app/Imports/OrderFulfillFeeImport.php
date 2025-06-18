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

            // Kiểm tra thiếu dữ liệu
            if (empty($extraId) || empty($sku)) {
                $this->skipped[] = "[Thiếu Extra ID hoặc SKU]";
                continue;
            }

            // Chuyển đổi fulfill_fee về float (xử lý dấu phẩy)
            $fulfillFee = is_numeric(str_replace(',', '.', $fulfillFeeRaw))
                ? floatval(str_replace(',', '.', $fulfillFeeRaw))
                : null;

            if ($fulfillFee === null) {
                $this->skipped[] = "$extraId / $sku - Fulfill Fee không hợp lệ";
                continue;
            }

            // Tìm đơn hàng theo cặp extra_id + sku
            $order = Order::where('extra_id', $extraId)
                ->where('sku', $sku)
                ->first();

            if ($order) {
                $order->fulfill_fee = $fulfillFee;

                // Tính lại profit
                $order->profit = $order->total - $order->cost - $fulfillFee;

                $order->save();
                $this->updated[] = "$extraId / $sku";
            } else {
                $this->skipped[] = "$extraId / $sku - Không tìm thấy đơn hàng";
            }
        }
    }
}
