<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiktokPayout extends Model
{
    use HasFactory;

    protected $table = 'tiktok_payouts';

    protected $fillable = [
        'shop_code',
        'payout_id',
        'payout_amount',
        'settlement_amount',
        'amount_before_exchange',
        'reserve_amount',
        'payout_initiation_date',
        'payout_completion_date',
        'status',
        'bank_account'
    ];

    protected $casts = [
        'payout_initiation_date' => 'datetime',
        'payout_completion_date' => 'datetime',
        'payout_amount' => 'float',
        'settlement_amount' => 'float',
        'amount_before_exchange' => 'float',
        'reserve_amount' => 'float',
    ];
}