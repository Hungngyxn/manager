<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user');           // ID người dùng (seller)
            $table->date('date');                         // Ngày báo cáo (daily aggregation)
            $table->integer('unit_sale')->default(0);     // Số lượng sản phẩm bán
            $table->decimal('revenue', 15, 2)->default(0);// Doanh thu
            $table->decimal('base_cost', 15, 2)->default(0); // Tổng giá vốn hàng bán
            $table->decimal('ads', 15, 2)->default(0);     // Chi phí quảng cáo trong ngày
            $table->decimal('profit', 15, 2)->default(0);  // Lợi nhuận
            $table->decimal('bonus', 15, 2)->default(0);   // Thưởng
            $table->timestamp('last_calculated_at')->nullable(); // Dấu thời gian lần cuối tính toán
            $table->timestamps();

            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['user', 'date'], 'reports_user_date_unique'); // mỗi user mỗi ngày 1 dòng
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
