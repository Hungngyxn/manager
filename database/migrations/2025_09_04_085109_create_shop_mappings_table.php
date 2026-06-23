<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopMappingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('shop_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name');
            $table->string('canonical_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_mappings');
    }
}
