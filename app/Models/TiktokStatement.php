<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiktokStatement extends Model
{
    use HasFactory;

    protected $table = 'tiktok_statements';

    protected $fillable = [
        'shop_code',
        'statement_date',
        'statement_id',
        'status',
        'settlement_amount',
        'net_sales',
        'shipping',
        'fee',
        'adjustment',
        'payout_id'
    ];

    protected $casts = [
        'statement_date' => 'datetime',
    ];
}