<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopAccount extends Model
{
    use HasFactory;

    protected $fillable = ['thang_reg', 'tuoi_acc', 'email', 'user_id', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sellerHasShop()
    {
        return $this->hasOne(SellerHasShop::class, 'email', 'email');
    }
}

