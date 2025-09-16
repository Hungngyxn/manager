<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerHasShop extends Model
{
    use HasFactory;

    protected $table = 'seller_has_shop';

    protected $fillable = [
        'user_id',
        'shop_name',
        'shop_code',
        'shop_cipher',
        'bank',
        'onhold',
        'pending',
        'payout',
        'email',
        'team_id'
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shop_name', 'shop_name');
    }

    public function token()
    {
        return $this->hasOne(TiktokToken::class, 'shop_name', 'shop_name');
    }
}
