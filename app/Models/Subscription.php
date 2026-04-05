<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'plan_variant_id',
        'status',
        'starts_at',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'grace_period_ends_at',
        'canceled_at',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'current_period_starts_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    protected $appends = [
        'has_access',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planVariant(): BelongsTo
    {
        return $this->belongsTo(PlanVariant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function getHasAccessAttribute(): bool
    {
        return $this->hasAccess();
    }

    public function hasAccess( $at = null): bool
    {
        $at = $at ?: now();

        return match ($this->status) {
            SubscriptionStatus::TRIALING => $this->trial_ends_at && $at->lte($this->trial_ends_at),
            SubscriptionStatus::ACTIVE => true,
            SubscriptionStatus::PAST_DUE => $this->grace_period_ends_at && $at->lte($this->grace_period_ends_at),
            SubscriptionStatus::CANCELED => false,
        };
    }
}
