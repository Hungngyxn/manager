<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSkuOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sku_orders', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->string('asin')->nullable();
            $table->string('name')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->integer('quantity_per_pack')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sku_orders');
    }
}
