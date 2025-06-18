<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tiktok_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_name')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('access_token');
            $table->string('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_tokens');
    }
};

