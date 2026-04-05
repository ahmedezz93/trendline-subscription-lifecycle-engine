<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanVariant;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubscriptionService
{
    public function create(array $data): Subscription
    {
        DB::beginTransaction();

        try {
            $plan = Plan::findOrFail($data['plan_id']);


            if ($plan->trial_days > 0) {
                $this->ensureUserDidNotUseTrialForPlanBefore($data['user_id'], $plan->id);
            }

            $planVariant = PlanVariant::whereKey($data['plan_variant_id'])
                ->where('plan_id', $plan->id)
                ->firstOrFail();

            $subscription = Subscription::create(
                $this->buildSubscriptionData($data, $plan, $planVariant)
            );

            DB::commit();

            return $subscription;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function buildSubscriptionData(array $data, Plan $plan, PlanVariant $planVariant): array
    {
        $now = now();

        $subscriptionData = [
            'user_id' => $data['user_id'],
            'plan_id' => $plan->id,
            'plan_variant_id' => $planVariant->id,
            'starts_at' => $now,
        ];

        if ($plan->trial_days > 0) {
            $subscriptionData['status'] = SubscriptionStatus::TRIALING;
            $subscriptionData['trial_ends_at'] = $now->copy()->addDays($plan->trial_days);

            return $subscriptionData;
        }

        $subscriptionData['status'] = SubscriptionStatus::ACTIVE;
        $subscriptionData['current_period_starts_at'] = $now;
        $subscriptionData['current_period_ends_at'] = $now->copy()
            ->addMonths($planVariant->billing_cycle->periodInMonths());

        return $subscriptionData;
    }



    private function ensureUserDidNotUseTrialForPlanBefore(int $userId, int $planId): void
    {
        $exists = Subscription::query()
            ->where('user_id', $userId)
            ->where('plan_id', $planId)
            ->whereNotNull('trial_ends_at')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'plan_id' => ['This user has already used the trial for this plan.'],
            ]);
        }
    }

}