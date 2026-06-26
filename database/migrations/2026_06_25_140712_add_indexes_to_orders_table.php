<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Thêm index cho bảng orders (gồm shop_code và ngày tạo)
        Schema::table('shop_us', function (Blueprint $table) {
            $table->index(['shop_code', 'created_at', 'status'], 'orders_shop_reports_index');
        });

        // 2. Thêm index cho bảng shops (gồm seller_id và shop_code)
        Schema::table('seller_has_shop', function (Blueprint $table) {
            $table->index(['user_id', 'shop_code'], 'shops_seller_index');
        });
    }

    public function down(): void
    {
        Schema::table('shop_us', function (Blueprint $table) {
            $table->dropIndex('orders_shop_report_index');
        });

        Schema::table('seller_has_shop', function (Blueprint $table) {
            $table->dropIndex('shops_seller_index');
        });
    }
};