<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('return_shipping', 10, 2)->default(0)->after('shipping_charge');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('returned_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('return_shipping'));
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('returned_quantity'));
    }
};
