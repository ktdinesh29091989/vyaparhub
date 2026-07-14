<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'sku', 'category', 'source_location',
        'cost_price', 'selling_price', 'gst_percent', 'stock', 'stock_threshold', 'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public const CATEGORIES = ['Saree', 'Kurti', 'Lehenga', 'Dupatta', 'Suit', 'Other'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockHistory::class)->latest();
    }

    /** Gross margin per unit (before channel/shipping costs). */
    public function getUnitMarginAttribute(): float
    {
        return (float) $this->selling_price - (float) $this->cost_price;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock <= $this->stock_threshold;
    }

    /**
     * Record a stock_history entry and keep the denormalized `stock` in sync.
     * $quantity is signed: positive adds stock, negative removes it.
     * $type must be one of: add, deduct, return, adjustment.
     */
    public function recordMovement(string $type, int $quantity, ?string $note = null, ?int $orderId = null): StockHistory
    {
        return DB::transaction(function () use ($type, $quantity, $note, $orderId) {
            $movement = $this->movements()->create([
                'user_id' => $this->user_id,
                'order_id' => $orderId,
                'type' => $type,
                'quantity' => $quantity,
                'note' => $note,
            ]);

            $this->increment('stock', $quantity);

            return $movement;
        });
    }
}
