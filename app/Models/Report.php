<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = 'reports';
    use HasFactory;

    protected $fillable = [
        'user',
        'unit_sale',
        'shop_name',
        'revenue',
        'profit',
        'base_cost',
        'ads',
        'bonus',
    ];

        public function userInfo()
    {
        return $this->belongsTo(User::class, 'user');
    }
}
