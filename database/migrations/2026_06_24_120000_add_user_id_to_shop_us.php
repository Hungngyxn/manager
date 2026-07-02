<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shop_us', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_us', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('shop_code');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index('user_id', 'shop_us_user_id_index');
            }

            // Index phục vụ tra cứu/đồng bộ (order_id) và lọc theo shop
            $table->index('order_id', 'shop_us_order_id_index');
            $table->index('shop_code', 'shop_us_shop_code_index');
        });

        // Backfill user_id cho dữ liệu cũ: shop_us.shop_code lưu shop_name
        // -> map qua seller_has_shop.shop_name để lấy user_id.
        DB::statement("
            UPDATE shop_us su
            JOIN seller_has_shop s ON s.shop_name = su.shop_code
            SET su.user_id = s.user_id
            WHERE s.user_id IS NOT NULL
              AND su.user_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('shop_us', function (Blueprint $table) {
            $table->dropForeign('shop_us_user_id_foreign');
            $table->dropIndex('shop_us_user_id_index');
            $table->dropIndex('shop_us_order_id_index');
            $table->dropIndex('shop_us_shop_code_index');
            $table->dropColumn('user_id');
        });
    }
};
