<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');                               // Meesho, Amazon, WhatsApp
            $table->string('slug');                               // meesho, amazon, whatsapp
            $table->decimal('commission_percent', 5, 2)->default(0);   // marketplace commission
            $table->decimal('shipping_charge', 8, 2)->default(0);      // default per-order shipping
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
