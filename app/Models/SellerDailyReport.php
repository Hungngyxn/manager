<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerDailyReport extends Model
{
    // 1. Khai báo tên bảng thực tế
    protected $table = 'seller_daily_reports';

    protected $fillable = [
        'seller_id',
        'report_date',
        'total_orders',
        'unit_sale',
        'total_revenue',
        'total_base_cost',
        'last_calculated_at'
    ];

    protected $casts = [
        'report_date'        => 'date:Y-m-d',
        'total_orders'       => 'integer',
        'unit_sale'          => 'integer',
        'total_revenue'      => 'decimal:2',
        'total_base_cost'    => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Liên kết ngược lại bảng Users để lấy thông tin Người bán
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'id');
    }
}