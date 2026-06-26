<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerMonthlyExpense extends Model
{
    // 1. Khai báo tên bảng thực tế
    protected $table = 'seller_monthly_expenses';

    // 2. Định nghĩa các trường được phép điền dữ liệu hàng loạt
    protected $fillable = [
        'seller_id',
        'month',
        'year',
        'ads_cost',
        'proxy_cost',
        'design_cost',
        'account_cost'
    ];

    // 3. Ép kiểu dữ liệu đầu ra cho các khoản chi phí
    protected $casts = [
        'month'        => 'integer',
        'year'         => 'integer',
        'ads_cost'     => 'decimal:2',
        'proxy_cost'   => 'decimal:2',
        'design_cost'  => 'decimal:2',
        'account_cost' => 'decimal:2',
    ];

    /**
     * Liên kết ngược lại bảng Users để lấy thông tin Người bán
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'id');
    }
}