<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackOrder extends Model
{
    use HasFactory;
       protected $fillable = [
        'date',
        'seller_id',
        'status',
        'email',
        'tracking_number',
        'driver_link',
        'sku',
        'carrier'
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}