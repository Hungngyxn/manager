<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSkuTable extends Migration
{
    public function up()
    {
        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->decimal('cost', 15, 2)->default(0);
            $table->string('name')->nullable();
            $table->integer('quantity');
            $table->string('tier')->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->boolean('freeshipping')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skus');
    }
}