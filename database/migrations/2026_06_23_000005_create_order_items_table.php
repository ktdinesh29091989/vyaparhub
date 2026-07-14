<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');          // snapshot, survives product deletion
            $table->string('sku')->nullable();        // snapshot
            $table->integer('quantity');
            $table->decimal('sale_price', 10, 2);     // unit price actually sold at
            $table->decimal('cost_price', 10, 2);     // unit cost captured at sale time
            $table->decimal('gst_percent', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
