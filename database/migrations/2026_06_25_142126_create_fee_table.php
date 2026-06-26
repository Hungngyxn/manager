<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seller_monthly_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->integer('month'); 
            $table->integer('year'); 
            
            // Các chi phí nhập tay cố định của cả tháng
            $table->decimal('ads_cost', 15, 2)->default(0.00);
            $table->decimal('proxy_cost', 15, 2)->default(0.00);
            $table->decimal('design_cost', 15, 2)->default(0.00);
            $table->decimal('account_cost', 15, 2)->default(0.00);
            
            $table->timestamps();

            // CHỈ MỤC TỐI ƯU (INDEX)
            $table->unique(['seller_id', 'month', 'year'], 'seller_month_year_unique');
            
            // Tối ưu tốc độ truy vấn tìm kiếm theo tháng năm khi JOIN hoặc WHERE
            $table->index(['month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_monthly_expenses');
    }
};