<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'extra_id',
        'order_id',
        'sku',
        'shop_name',
        'quantity',
        'cost',
        'total',
        'profit',
        'bonus',
        'fulfill_fee',
        'user_id',
        'product_name'
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function skuInfo()
    {
        return $this->belongsTo(Sku::class, 'sku', 'sku');
    }

    public function setSkuAttribute($value)
    {
        $this->attributes['sku'] = strtolower($value);
    }
}
