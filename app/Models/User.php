<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Free-plan limits. */
    public const FREE_PRODUCT_LIMIT = 5;
    public const FREE_MONTHLY_ORDER_LIMIT = 15;
    public const PRO_PRICE_RUPEES = 299;
    public const ANNUAL_PRICE_RUPEES = 2499;

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** Create the default sales channels for a new seller (idempotent). */
    public function ensureDefaultChannels(): void
    {
        if ($this->channels()->exists()) {
            return;
        }

        foreach (Channel::DEFAULTS as $channel) {
            $this->channels()->create($channel);
        }
    }

    /** True only while an active (unexpired) Pro subscription is in effect. */
    public function isPro(): bool
    {
        return $this->plan === 'pro' && $this->plan_expires_at && $this->plan_expires_at->isFuture();
    }

    /** True while Pro is active and will lapse within the given number of days. */
    public function isExpiringSoon(int $withinDays = 7): bool
    {
        return $this->isPro() && now()->diffInDays($this->plan_expires_at) <= $withinDays;
    }

    /**
     * If a Pro subscription has lapsed, silently revert to the free plan.
     * Called on every authenticated request via CheckPlanStatus middleware.
     */
    public function downgradeIfExpired(): void
    {
        if ($this->plan === 'pro' && $this->plan_expires_at && $this->plan_expires_at->isPast()) {
            $this->forceFill(['plan' => 'free', 'plan_expires_at' => null])->save();
        }
    }

    public function activateProPlan(int $days = 30, string $planType = 'monthly'): void
    {
        $this->forceFill([
            'plan' => 'pro',
            'plan_expires_at' => now()->addDays($days),
            'plan_type' => $planType,
        ])->save();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'business_name',
        'email',
        'mobile',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
