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
            $excelRow = $index + 2; // do heading row = row 1
            $shopName = trim($row['shop_name'] ?? '');
            $shopCode = trim($row['shop_code'] ?? '');
            $sellerName = trim($row['seller_name'] ?? '');

            // Bỏ qua dòng trống hoàn toàn
            if (empty($shopName) && empty($sellerName)) {
                continue;
            }

            // Nếu thiếu shop_name thì skip
            if (empty($shopName)) {
                $this->skipped[] = $excelRow;
                continue;
            }

            // Tìm user, nếu không có thì là unassigned
            $user = User::where('name', $sellerName)->first();

            // Nếu shop đã tồn tại thì bỏ qua
            $existingShop = SellerHasShop::where('shop_name', $shopName)->first();
            if ($existingShop) {
                continue;
            }

            // Tạo mới shop
            SellerHasShop::create([
                'shop_name' => $shopName,
                'shop_code' => $shopCode,
                'user_id'   => $user?->id, // có thể là null nếu không tìm thấy user
            ]);

            $this->created[] = "$shopName (code: $shopCode, seller: " . ($sellerName ?: 'Unassigned') . ")";
        }
    }
}
