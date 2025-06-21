<?php

namespace App\Imports;

use App\Models\SellerHasShop;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ShopImport implements ToCollection, WithHeadingRow
{
    public $created = [];
    public $skipped = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            $excelRow = $index + 2;
            $shopName = trim($row['shop_name']);
            $shopCode = trim($row['shop_code'] ?? null);
            $sellerName = trim($row['seller_name'] ?? '');


            if (empty($shopName) || empty($sellerName)) {
                $this->skipped[] = $excelRow;
                continue;
            }

            $user = User::where('name', $sellerName)->first();

            if (!$user) {
                $this->skipped[] = $excelRow;
                continue;
            }

            $existingShop = SellerHasShop::where('shop_name', $shopName)->first();
            if ($existingShop) {
                continue;
            }

            SellerHasShop::create([
                'shop_name' => $shopName,
                'shop_code' => $shopCode,
                'user_id' => $user->id,
            ]);

            $this->created[] = "$shopName (code: $shopCode, seller: $sellerName)";
        }
    }
}
