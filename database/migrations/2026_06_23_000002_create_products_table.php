<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('category')->nullable();               // Saree, Kurti, Lehenga...
            $table->decimal('cost_price', 10, 2)->default(0);     // what the seller paid
            $table->decimal('selling_price', 10, 2)->default(0);  // listed price
            $table->decimal('gst_percent', 5, 2)->default(5);     // textiles are usually 5%
            $table->integer('stock')->default(0);                 // denormalized on-hand qty
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
