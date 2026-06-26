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
        Schema::create('seller_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->date('report_date'); // Ngày báo cáo (Định dạng: YYYY-MM-DD)
            
            // CHỈ SỐ THÔ TỰ ĐỘNG (Gom nhóm dữ liệu thô từ bảng orders theo ngày)
            $table->integer('total_orders')->default(0);                      
            $table->integer('unit_sale')->default(0);                         
            $table->decimal('total_revenue', 15, 2)->default(0.00);    
            $table->decimal('total_base_cost', 15, 2)->default(0.00);  
            
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            // RÀNG BUỘC & CHỈ MỤC TỐI ƯU SIÊU TỐC
            // Đảm bảo 1 Seller chỉ có duy nhất 1 dòng dữ liệu cho 1 ngày
            $table->unique(['seller_id', 'report_date'], 'seller_report_date_unique'); 
            
            $table->index('report_date');
            
            $table->index(['seller_id', 'report_date'], 'seller_date_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_daily_reports');
    }
};