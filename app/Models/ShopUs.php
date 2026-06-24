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
        'user_id',
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
        'price'
    ];

    protected $casts = [
        'products' => 'array', // để Laravel tự decode JSON thành mảng
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
