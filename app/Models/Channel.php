<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Channel extends Model
{
    protected $fillable = [
        'user_id', 'name', 'slug', 'commission_percent', 'shipping_charge', 'is_active',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** The default channels every seller starts with. */
    public const DEFAULTS = [
        ['name' => 'Meesho',   'slug' => 'meesho',   'commission_percent' => 2, 'shipping_charge' => 50],
        ['name' => 'Amazon',   'slug' => 'amazon',   'commission_percent' => 3, 'shipping_charge' => 60],
        ['name' => 'WhatsApp', 'slug' => 'whatsapp', 'commission_percent' => 0, 'shipping_charge' => 0],
        ['name' => 'Local',    'slug' => 'local',    'commission_percent' => 0, 'shipping_charge' => 0],
    ];

    /** Tailwind badge classes keyed by channel slug, for the orders list/create-form UI. */
    public const BADGE_COLORS = [
        'meesho' => 'bg-pink-100 text-pink-700',
        'amazon' => 'bg-orange-100 text-orange-700',
        'whatsapp' => 'bg-emerald-100 text-emerald-700',
        'local' => 'bg-blue-100 text-blue-700',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getBadgeColorAttribute(): string
    {
        return self::BADGE_COLORS[$this->slug] ?? 'bg-slate-100 text-slate-600';
    }
}
