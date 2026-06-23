<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkuOrder extends Model
{
    use HasFactory;
    protected $table = 'sku_orders';

    protected $fillable = [
        'sku',
        'asin',
        'name',
        'warehouse_name',
        'quantity_per_pack',
    ];
}

