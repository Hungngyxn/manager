<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{
    use HasFactory;

    protected $table = 'skus';

    protected $casts = [
        'freeshipping' => 'boolean',
    ];

    protected $fillable = ['sku', 'name', 'cost', 'quantity', 'tier', 'price', 'freeshipping'];

    public function orders()
    {
        return $this->hasMany(Order::class, 'sku', 'sku');
    }

    public function tierBonus()
    {
        return $this->belongsTo(Tier::class, 'tier', 'tier');
    }
}