<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('track_order', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('email')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamps();

            $table->foreign('seller_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('track_order');
    }
};
