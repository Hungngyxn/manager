<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopMapping extends Model
{
    protected $fillable = ['shop_name', 'canonical_name'];

    // Hàm tiện ích để lấy tên chuẩn
    public static function resolveShopName(string $shopName): string
    {
        $shopName = strtolower(trim($shopName));

        $mapping = self::whereRaw('LOWER(shop_name) = ?', [$shopName])->first();

        return $mapping->canonical_name ?? $shopName;
    }
}
