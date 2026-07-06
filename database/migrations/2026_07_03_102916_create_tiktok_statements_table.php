<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiktok_statements', function (Blueprint $table) {
            $table->id();
            $table->string('shop_code')->index();
            $table->dateTime('statement_date');
            $table->string('statement_id')->unique();
            $table->string('status'); // PAID, PROCESSING, v.v.
            $table->decimal('settlement_amount', 12, 2);
            $table->decimal('net_sales', 12, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('adjustment', 12, 2)->default(0);
            $table->string('payout_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_statements');
    }
};