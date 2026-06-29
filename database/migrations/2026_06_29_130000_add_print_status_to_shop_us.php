<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shop_us', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_us', 'print_provider')) {
                $table->string('print_provider')->nullable()->after('print');
            }
            if (!Schema::hasColumn('shop_us', 'provider_order_id')) {
                // Mã đơn nhà in trả về (FlashShip: order_code)
                $table->string('provider_order_id')->nullable()->after('print_provider');
            }
            if (!Schema::hasColumn('shop_us', 'print_status')) {
                // not_sent | sending | sent | pending_payment | failed
                $table->string('print_status')->default('not_sent')->after('provider_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shop_us', function (Blueprint $table) {
            foreach (['print_provider', 'provider_order_id', 'print_status'] as $col) {
                if (Schema::hasColumn('shop_us', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
