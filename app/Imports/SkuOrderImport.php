<?php

namespace App\Imports;

use App\Models\SkuOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SkuOrderImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $sku = trim($row['sku'] ?? '');
            $quantityPerPack = intval($row['quantitypack'] ?? 0);


            // Bỏ qua nếu thiếu SKU hoặc quantity_per_pack <= 0
            if (empty($sku) || $quantityPerPack <= 0) {
                continue;
            }

            SkuOrder::updateOrCreate(
                ['warehouse_name' => $row['ten_sp_kho_us'] ?? null],
                [
                    'asin' => $row['asin'] ?? null,
                    'product_name' => $row['mat_hang'] ?? null,
                    'sku' => $sku ?? null,
                    'quantity_per_pack' => $quantityPerPack,
                ]
            );
        }
    }
}
