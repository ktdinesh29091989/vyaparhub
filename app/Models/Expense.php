<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'user_id', 'spent_on', 'category', 'description', 'amount',
    ];

    protected $casts = [
        'spent_on' => 'date',
        'amount' => 'decimal:2',
    ];

    public const CATEGORIES = ['Packaging', 'Advertising', 'Rent', 'Salaries', 'Transport', 'Utilities', 'Misc'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
