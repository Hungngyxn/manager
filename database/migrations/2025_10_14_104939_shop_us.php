<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_us', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->string('product_image')->nullable();

            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();

            $table->string('tracking_number')->nullable();
            $table->string('label_link')->nullable();

            $table->enum('status', ['Pending', 'Shipped', 'Delivered', 'Cancelled'])->default('Pending');
            $table->decimal('total_amount', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_us');
    }
};
