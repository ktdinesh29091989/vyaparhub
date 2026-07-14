<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Old stock_movements.type => new stock_history.type */
    private const TYPE_MAP = [
        'opening' => 'add',
        'purchase' => 'add',
        'sale' => 'deduct',
        'return' => 'return',
        'adjustment' => 'adjustment',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('stock_movements')) {
            DB::table('stock_movements')->orderBy('id')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('stock_history')->insert([
                        'product_id' => $row->product_id,
                        'user_id' => $row->user_id,
                        'type' => self::TYPE_MAP[$row->type] ?? 'adjustment',
                        'quantity' => $row->quantity,
                        'note' => $row->note,
                        'created_at' => $row->created_at,
                    ]);
                }
            });

            Schema::drop('stock_movements');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->integer('quantity');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }
};
