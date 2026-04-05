<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $plan = Plan::factory()->create();
        $price = $plan->planVariants()->create([
            'billing_cycle' => BillingCycle::MONTHLY,
            'currency' => Currency::AED,
            'amount' => 100,
        ]);

        return [
            'user_id' => User::factory(),
            'plan_id' => $plan->id,
            'plan_variant_id' => $price->id,
            'status' => SubscriptionStatus::TRIALING,
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(7),
        ];
    }

    public function active(): static
    {
        return $this->state(function () {
            return [
                'status' => SubscriptionStatus::ACTIVE,
                'trial_ends_at' => null,
                'current_period_starts_at' => now(),
                'current_period_ends_at' => now()->addMonth(),
            ];
        });
    }
}
