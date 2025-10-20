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
        'product_name',
        'sku',
        'product_image',
        'customer_name',
        'customer_phone',
        'customer_address',
        'tracking_number',
        'label_link',
        'status',
        'total_amount',
    ];
}
