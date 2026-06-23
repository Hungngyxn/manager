<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_us', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable();
            $table->string('shop_code')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_country')->nullable();
            $table->string('customer_state')->nullable();
            $table->string('customer_city')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_postcode')->nullable();

            $table->json('products')->nullable(); // lưu danh sách sản phẩm dạng JSON
            $table->string('tracking_number')->nullable();
            $table->string('label_link')->nullable();

            $table->string('status')->default('Unknown');
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_us');
    }
};
