<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellerHasShopTable extends Migration
{
    public function up(): void
    {
        Schema::create('seller_has_shop', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            // Thông tin shop
            $table->string('shop_code')->unique();
            $table->string('shop_cipher')->unique();
            $table->string('shop_name');
            $table->string('bank')->nullable();
            $table->decimal('onhold', 10, 2)->default(0);
            $table->decimal('payout', 10, 2)->default(0);

            // Thời gian gán seller
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('unassigned_at')->nullable();

            $table->timestamps();

            // Ràng buộc khóa ngoại
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_has_shop');
    }
}
