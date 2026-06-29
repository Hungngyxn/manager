<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopUs extends Model
{
    use HasFactory;

    protected $table = 'shop_us';

    protected $fillable = [
        'order_id',
        'customer_name',
        'customer_phone',
        'customer_country',
        'customer_state',
        'customer_city',
        'customer_address',
        'customer_postcode',
        'shop_code',
        'products',
        'tracking_number',
        'label_link',
        'status',
        'total_amount',
        'price',
        'print',
    ];

    protected $casts = [
        'products' => 'array', // để Laravel tự decode JSON thành mảng
        'print' => 'array',    // thông tin print cấp đơn (shipment, printer, shipping_label_url)
    ];

    public function sellerHasShop()
    {
        // Liên kết ngược về bảng seller_has_shop qua cột shop_code
        return $this->belongsTo(SellerHasShop::class, 'shop_code', 'shop_code');
    }
    /**
     * shop_us KHÔNG có user_id. Liên kết qua seller_has_shop:
     * shop_us.shop_code = seller_has_shop.shop_code.
     */
    public function shop()
    {
        return $this->belongsTo(SellerHasShop::class, 'shop_code', 'shop_code');
    }

    /**
     * Seller sở hữu đơn, suy ra qua seller_has_shop.user_id.
     */
    public function seller()
    {
        return $this->hasOneThrough(
            User::class,
            SellerHasShop::class,
            'shop_code', // FK trên seller_has_shop khớp local key của shop_us
            'id',        // PK trên users
            'shop_code', // local key trên shop_us
            'user_id'    // local key trên seller_has_shop -> users.id
        );
    }
}
