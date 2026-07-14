<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'channel_id', 'order_number', 'order_date', 'customer_name',
        'customer_phone', 'status', 'source', 'subtotal', 'shipping_charge',
        'return_shipping', 'commission_amount', 'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'subtotal' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'return_shipping' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public const STATUSES = ['placed', 'shipped', 'delivered', 'partially_returned', 'rto', 'returned', 'cancelled'];

    /** Statuses a seller can set manually; returns are recorded via the return flow instead. */
    public const MANUAL_STATUSES = ['placed', 'shipped', 'delivered', 'cancelled'];

    /** Statuses where the whole order's goods are off the seller's books (not a completed sale). */
    public const RESTOCK_STATUSES = ['rto', 'returned', 'cancelled'];

    /** Active orders that can still receive a return. */
    public const RETURNABLE_STATUSES = ['placed', 'shipped', 'delivered', 'partially_returned'];

    /** Once here, no further manual status change is allowed (return flow is separately gated too). */
    public const LOCKED_STATUSES = ['delivered', 'returned', 'rto'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockHistory(): HasMany
    {
        return $this->hasMany(StockHistory::class)->latest();
    }

    public function getIsLockedAttribute(): bool
    {
        return in_array($this->status, self::LOCKED_STATUSES, true);
    }

    /** Recalculate subtotal from the line items. */
    public function recalcSubtotal(): void
    {
        $this->subtotal = $this->items->sum(fn ($i) => $i->quantity * $i->sale_price);
    }

    /** Net profit after cost, commission, shipping and returns (via ProfitCalculator). */
    public function getNetProfitAttribute(): float
    {
        return app(\App\Services\ProfitCalculator::class)->netProfit($this->loadMissing('items'));
    }

    /** Total units returned across the order's items. */
    public function getReturnedUnitsAttribute(): int
    {
        if (in_array($this->status, ['rto', 'returned', 'cancelled'], true)) {
            return (int) $this->items->sum('quantity');
        }

        return (int) $this->items->sum('returned_quantity');
    }

    public function getStatusColorAttribute(): string
    {
        return [
            'placed' => 'bg-blue-100 text-blue-700',
            'shipped' => 'bg-indigo-100 text-indigo-700',
            'delivered' => 'bg-emerald-100 text-emerald-700',
            'partially_returned' => 'bg-orange-100 text-orange-700',
            'rto' => 'bg-amber-100 text-amber-700',
            'returned' => 'bg-red-100 text-red-700',
            'cancelled' => 'bg-slate-200 text-slate-600',
        ][$this->status] ?? 'bg-slate-100 text-slate-600';
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }
}
