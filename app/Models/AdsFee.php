<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdsFee extends Model
{
    protected $fillable = ['user_id', 'date', 'ads'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
