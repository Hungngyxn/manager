<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    use HasFactory;

protected $table = 'statements';

    protected $fillable = [
        'user_id',
        'order_id',
        'total_settlement',
        'product_name',
        'tier',
        'fulfill_fee',
        'status',
    ];
}
