<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'sku',
        'quantity', 'returned_quantity', 'sale_price', 'cost_price', 'gst_percent',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'gst_percent' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getLineTotalAttribute(): float
    {
        return $this->quantity * (float) $this->sale_price;
    }

    public function getSoldQuantityAttribute(): int
    {
        return max(0, $this->quantity - (int) $this->returned_quantity);
    }

    /** How many more units of this line can still be returned. */
    public function getReturnableQuantityAttribute(): int
    {
        return max(0, $this->quantity - (int) $this->returned_quantity);
    }
}
