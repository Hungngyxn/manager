<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'team_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function paginate($count = 10)
    {
        return $this->with('role')->latest()->paginate($count);
    }

    public function isAdmin()
    {
        return $this->role->isAdmin();
    }

    public function shops()
    {
        return $this->hasMany(SellerHasShop::class, 'user_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    protected static function booted()
    {
        static::deleting(function ($seller) {
            SellerHasShop::where('user_id', $seller->id)->update(['user_id' => null]);
        });

    }
}
