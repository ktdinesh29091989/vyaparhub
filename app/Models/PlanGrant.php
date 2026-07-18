<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanGrant extends Model
{
    protected $fillable = [
        'user_id', 'granted_by', 'previous_plan', 'previous_expires_at',
        'new_plan', 'new_plan_type', 'new_expires_at', 'days_granted', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'previous_expires_at' => 'datetime',
            'new_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
