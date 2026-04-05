<?php

namespace Database\Seeders;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $basic = Plan::query()->create([
            'name' => 'Basic',
            'trial_days' => 7,
            'is_active' => true,
        ]);

        $pro = Plan::query()->create([
            'name' => 'Pro',
            'trial_days' => 0,
            'is_active' => true,
        ]);

        foreach ([
            [$basic, BillingCycle::MONTHLY, Currency::AED, 49.00],
            [$basic, BillingCycle::YEARLY, Currency::AED, 499.00],
            [$basic, BillingCycle::MONTHLY, Currency::USD, 13.00],
            [$basic, BillingCycle::YEARLY, Currency::USD, 129.00],
            [$basic, BillingCycle::MONTHLY, Currency::EGP, 650.00],
            [$basic, BillingCycle::YEARLY, Currency::EGP, 6500.00],
            [$pro, BillingCycle::MONTHLY, Currency::AED, 99.00],
            [$pro, BillingCycle::YEARLY, Currency::AED, 999.00],
            [$pro, BillingCycle::MONTHLY, Currency::USD, 27.00],
            [$pro, BillingCycle::YEARLY, Currency::USD, 270.00],
            [$pro, BillingCycle::MONTHLY, Currency::EGP, 1300.00],
            [$pro, BillingCycle::YEARLY, Currency::EGP, 13000.00],
        ] as [$plan, $billingCycle, $currency, $amount]) {
            $plan->planVariants()->create([
                'billing_cycle' => $billingCycle,
                'currency' => $currency,
                'amount' => $amount,
            ]);
        }
    }
}
