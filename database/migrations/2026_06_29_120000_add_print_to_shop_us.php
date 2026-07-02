<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shop_us', function (Blueprint $table) {
            if (!Schema::hasColumn('shop_us', 'print')) {
                // Thông tin print cấp đơn (shipment, printer, shipping_label_url).
                // Thông tin print theo từng sản phẩm được gộp vào cột `products`.
                $table->json('print')->nullable()->after('products');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shop_us', function (Blueprint $table) {
            if (Schema::hasColumn('shop_us', 'print')) {
                $table->dropColumn('print');
            }
        });
    }
};
