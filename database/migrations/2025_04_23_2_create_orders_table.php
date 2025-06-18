<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('extra_id');
            $table->string('order_id');
            $table->string('sku');
            $table->string('shop_name');
            $table->string('user_id');
            $table->string('shop_cipher');
            $table->integer('quantity');
            $table->decimal('cost', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('profit', 10, 2);
            $table->decimal('bonus', 10, 2);
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
