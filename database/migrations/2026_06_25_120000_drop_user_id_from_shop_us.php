<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shop_us', function (Blueprint $table) {
            if (Schema::hasColumn('shop_us', 'user_id')) {
                $table->dropForeign('shop_us_user_id_foreign');
                $table->dropIndex('shop_us_user_id_index');
                $table->dropColumn('user_id');
            }
        });
        // Giữ lại index order_id / shop_code (hữu ích cho join + tra cứu).
    }

    public function down(): void
    {
        Schema::table('shop_us', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_us', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('shop_code');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index('user_id', 'shop_us_user_id_index');
            }
        });
    }
};
