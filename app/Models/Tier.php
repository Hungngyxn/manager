<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tier extends Model
{
    use HasFactory;
    protected $fillable = ['tier', 'bonus'];

    public function skus()
    {
        return $this->hasMany(Sku::class, 'tier', 'tier');
    }
}
