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
        'user_id', 'name', 'sku', 'product_type', 'category', 'custom_attributes', 'source_location',
        'cost_price', 'selling_price', 'gst_percent', 'stock', 'stock_threshold', 'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'custom_attributes' => 'array',
    ];

    /** Garment/product sub-type (textile-specific), shown in the "Product type" dropdown. Was formerly CATEGORIES. */
    public const PRODUCT_TYPES = ['Saree', 'Kurti', 'Lehenga', 'Dupatta', 'Suit', 'Other'];

    /** Business vertical the product belongs to: slug => display label. */
    public const CATEGORIES = [
        'textile' => 'Textile',
        'cosmetics' => 'Cosmetics',
        'footwear' => 'Footwear',
        'jewelry' => 'Jewelry',
        'kids_wear' => 'Kids Wear',
        'electronics_accessories' => 'Electronics Accessories',
        'other' => 'Other',
    ];

    /** Extra custom_attributes fields to render per category, keyed by category slug. */
    public const CATEGORY_FIELDS = [
        'textile' => [
            ['key' => 'fabric_type', 'label' => 'Fabric type', 'type' => 'text'],
        ],
        'footwear' => [
            ['key' => 'size', 'label' => 'Size', 'type' => 'text'],
            ['key' => 'color', 'label' => 'Color', 'type' => 'text'],
        ],
        'cosmetics' => [
            ['key' => 'shade', 'label' => 'Shade', 'type' => 'text'],
            ['key' => 'expiry_date', 'label' => 'Expiry date', 'type' => 'date'],
        ],
        'jewelry' => [
            ['key' => 'material', 'label' => 'Material', 'type' => 'text'],
            ['key' => 'weight', 'label' => 'Weight', 'type' => 'text'],
        ],
        'kids_wear' => [
            ['key' => 'age_group', 'label' => 'Age group', 'type' => 'text'],
            ['key' => 'size', 'label' => 'Size', 'type' => 'text'],
        ],
        'electronics_accessories' => [
            ['key' => 'compatibility', 'label' => 'Compatibility', 'type' => 'text'],
            ['key' => 'color', 'label' => 'Color', 'type' => 'text'],
        ],
        'other' => [
            ['key' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ],
    ];

    /** Chart colors per category, matching the badge hues used on the products list. */
    public const CATEGORY_CHART_COLORS = [
        'textile' => '#9333ea',
        'cosmetics' => '#db2777',
        'footwear' => '#d97706',
        'jewelry' => '#ca8a04',
        'kids_wear' => '#0284c7',
        'electronics_accessories' => '#4f46e5',
        'other' => '#64748b',
    ];

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
