<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'billing_cycle',
        'currency',
        'amount',
    ];

    protected $casts = [
        'billing_cycle' => BillingCycle::class,
        'currency' => Currency::class,
        'amount' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
