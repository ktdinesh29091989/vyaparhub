<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Kept even if the user is later deleted, so revenue history/audit trail survives.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('razorpay_payment_id')->unique();
            $table->string('razorpay_order_id')->nullable();
            $table->string('plan_type'); // monthly, annual
            $table->decimal('amount', 10, 2); // rupees
            $table->string('status')->default('captured');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
