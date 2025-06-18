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
        foreach ($rows as $row) {
            $shopName = trim($row['shop_name'] ?? '');
            $shopCode = trim($row['shop_code'] ?? '');
            $sellerName = trim($row['seller_name'] ?? '');

            if (empty($shopName) || empty($sellerName)) {
                $this->skipped[] = "[Thiếu dữ liệu] Shop: $shopName / Seller: $sellerName";
                continue;
            }

            $user = User::where('name', $sellerName)->first();

            if (!$user) {
                $this->skipped[] = "[Không tìm thấy seller] $sellerName";
                continue;
            }

            // Nếu đã có shop thì bỏ qua
            $existingShop = SellerHasShop::where('shop_name', $shopName)->first();
            if ($existingShop) {
                $this->skipped[] = "[Shop đã tồn tại] $shopName";
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
