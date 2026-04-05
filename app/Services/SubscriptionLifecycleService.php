<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionLifecycleService
{
    public function moveToPastDue(Subscription $subscription): Subscription
    {
        $this->ensureTransitionAllowed($subscription, SubscriptionStatus::PAST_DUE);

        $subscription->update([
            'status' => SubscriptionStatus::PAST_DUE,
            'grace_period_ends_at' => now()->addDays(3),
        ]);

        return $subscription->fresh();
    }

    public function activate(Subscription $subscription): Subscription
    {
        $this->ensureTransitionAllowed($subscription, SubscriptionStatus::ACTIVE);

        $billingCycle = $subscription->planVariant->billing_cycle;

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->copy()->addMonths($billingCycle->periodInMonths()),
            'trial_ends_at' => null,
            'grace_period_ends_at' => null,
            'canceled_at' => null,
        ]);

        return $subscription->fresh();
    }

    public function cancel($id)
    {
        $subscription = Subscription::find($id);

        if (!$subscription) {
            throw ValidationException::withMessages([
                'subscription_id' => ['Subscription not found.'],
            ]);
        }
        $this->ensureTransitionAllowed($subscription, SubscriptionStatus::CANCELED);

        $subscription->update([
            'status' => SubscriptionStatus::CANCELED,
            'canceled_at' => now(),
            'grace_period_ends_at' => null,
        ]);

        return $subscription->fresh();
    }

    public function processDailyLifecycle(): array
    {
        return DB::transaction(function () {
            $expiredTrials = Subscription::query()
                ->where('status', SubscriptionStatus::TRIALING)
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '<', now())
                ->lockForUpdate()
                ->get();

            $expiredTrialsCount = 0;

            foreach ($expiredTrials as $subscription) {
                $this->cancel($subscription);
                $expiredTrialsCount++;
            }

            $expiredGracePeriods = Subscription::query()
                ->where('status', SubscriptionStatus::PAST_DUE)
                ->whereNotNull('grace_period_ends_at')
                ->where('grace_period_ends_at', '<', now())
                ->lockForUpdate()
                ->get();

            $expiredGraceCount = 0;

            foreach ($expiredGracePeriods as $subscription) {
                $this->cancel($subscription);
                $expiredGraceCount++;
            }

            return [
                'expired_trials_processed' => $expiredTrialsCount,
                'grace_period_cancellations_processed' => $expiredGraceCount,
            ];
        });
    }

    private function ensureTransitionAllowed(Subscription $subscription, SubscriptionStatus $targetStatus): void
    {
        $allowedTransitions = [
            SubscriptionStatus::TRIALING->value => [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::CANCELED,
            ],
            SubscriptionStatus::ACTIVE->value => [
                SubscriptionStatus::PAST_DUE,
                SubscriptionStatus::CANCELED,
            ],
            SubscriptionStatus::PAST_DUE->value => [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::CANCELED,
            ],
            SubscriptionStatus::CANCELED->value => [],
        ];

        $allowed = collect($allowedTransitions[$subscription->status->value] ?? [])
            ->contains(fn($status) => $status === $targetStatus);

        if (! $allowed) {
            throw ValidationException::withMessages([
                'status' => [
                    "Transition from {$subscription->status->value} to {$targetStatus->value} is not allowed."
                ],
            ]);
        }
    }
}
