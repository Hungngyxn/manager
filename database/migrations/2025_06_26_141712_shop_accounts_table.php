<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_accounts', function (Blueprint $table) {
            $table->id();
            $table->date('thang_reg')->nullable();
            $table->string('tuoi_acc')->default(0); 
            $table->string('email')->unique(); 
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_accounts');
    }
};
