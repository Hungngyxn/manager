<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user', 'date', 'unit_sale', 'revenue', 'base_cost', 'ads', 'profit', 'bonus', 'last_calculated_at'
    ];

    public function userInfo()
    {
        return $this->belongsTo(User::class, 'user');
    }
}
