<?php

namespace App\Imports;

use App\Models\ProductList;
use App\Models\Sku;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductListImport implements ToCollection, WithHeadingRow
{
    public $imported = [];

    // Chỉ định heading row bắt đầu từ dòng 3
    public function headingRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (empty($row['product_name']) && empty($row['variation_option']) && empty($row['seller_sku'])) {
                continue;
            }

            $productName = $row['product_name'];
            $variation = $row['variation_option'];
            $sellerSku = $row['seller_sku'];
            $sku_code = $this->parseSkuWithPack($sellerSku);
            $tier = Sku::where('sku', $sku_code)->value('tier');

            $product = new ProductList([
                'product_name' => $productName,
                'variation' => $variation,
                'sku_code' => $sku_code,
                'tier' => $tier,
            ]);

            $product->save();
        }
    }

    protected function parseSkuWithPack(string $rawSku): string
    {
        // Nếu SKU gốc tồn tại, không cần xử lý pack
        if (Sku::where('sku', $rawSku)->exists()) {
            return $rawSku;
        }

        // Tách số lượng từ pattern như Pack2, 2pack, pack_3, _pack2, v.v.
        if (preg_match('/(?:^|_)?(?:pack)?(\d+)(?:pack)?(?:_|$)/i', $rawSku, $matches)) {

            // Loại bỏ phần "pack" ra khỏi SKU
            $cleanSku = preg_replace('/(?:^|_)?(?:pack)?\d+(?:pack)?(?:_|$)/i', '_', $rawSku);
            $cleanSku = trim($cleanSku, '_');

            return $cleanSku;
        }

        // Không match pattern → trả nguyên
        return $rawSku;
    }
}
