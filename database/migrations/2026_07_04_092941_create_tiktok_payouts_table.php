<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiktok_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('shop_code')->index();
            $table->string('payout_id')->unique();
            $table->decimal('payout_amount', 15, 2)->default(0.00);
            $table->decimal('settlement_amount', 15, 2)->default(0.00);
            $table->decimal('amount_before_exchange', 15, 2)->default(0.00);
            $table->decimal('reserve_amount', 15, 2)->default(0.00);
            $table->dateTime('payout_initiation_date')->nullable();
            $table->dateTime('payout_completion_date')->nullable();
            $table->string('status')->default('Processing');
            $table->string('bank_account')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_payouts');
    }
};