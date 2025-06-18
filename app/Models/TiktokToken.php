<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TiktokToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_name',
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $dates = ['expires_at'];
}
