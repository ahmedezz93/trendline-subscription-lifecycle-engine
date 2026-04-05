<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService
{
    public function __construct(
        private readonly SubscriptionLifecycleService $lifecycleService
    ) {}


    public function recordSuccess($id, array $data): Subscription
    {
        DB::beginTransaction();

        try {
            $subscription = Subscription::query()
                ->whereKey($id)
                ->with(['planVariant', 'payments'])
                ->lockForUpdate()
                ->first();
            if (! $subscription) {
                throw ValidationException::withMessages([
                    'subscription_id' => ['Subscription not found.'],
                ]);
            }

            if (! $subscription->planVariant) {
                throw ValidationException::withMessages([
                    'plan_variant_id' => ['Subscription plan variant is missing.'],
                ]);
            }

            $alreadyProcessed = $subscription->payments()
                ->where('transaction_reference', $data['transaction_reference'])
                ->exists();

            if ($alreadyProcessed) {
                DB::commit();
                return $subscription->fresh(['plan', 'planVariant', 'payments']);
            }

            $this->ensureSuccessAllowed($subscription);

            $amount = $data['amount'] ?? $subscription->planVariant->amount;
            $currency = $data['currency'] ?? $subscription->planVariant->currency->value;

            if ((float) $amount !== (float) $subscription->planVariant->amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment amount does not match the plan variant amount.'],
                ]);
            }

            if ($currency !== $subscription->planVariant->currency->value) {
                throw ValidationException::withMessages([
                    'currency' => ['Payment currency does not match the plan variant currency.'],
                ]);
            }

            $this->createPayment($subscription, [
                'amount' => $amount,
                'currency' => $currency,
                'status' => PaymentStatus::SUCCESS,
                'transaction_reference' => $data['transaction_reference'],
                'processed_at' => now(),
            ]);

            $updatedSubscription = $this->lifecycleService->activate(
                $subscription->fresh(['planVariant'])
            );

            DB::commit();

            return $updatedSubscription->load(['plan', 'planVariant', 'payments']);
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recordFailure($id, array $data = [])
    {
        DB::beginTransaction();

        try {
            $subscription = Subscription::query()
                ->whereKey($id)
                ->with(['planVariant', 'payments'])
                ->lockForUpdate()
                ->first();
            if (! $subscription) {
                throw ValidationException::withMessages([
                    'subscription_id' => ['Subscription not found.'],
                ]);
            }

            if (! $subscription->planVariant) {
                throw ValidationException::withMessages([
                    'plan_variant_id' => ['Subscription plan variant is missing.'],
                ]);
            }

            $this->ensureFailureAllowed($subscription);

            // optional duplicate protection
            if (!empty($data['transaction_reference'])) {
                $exists = $subscription->payments()
                    ->where('transaction_reference', $data['transaction_reference'])
                    ->exists();

                if ($exists) {
                    DB::commit();
                    return $subscription->fresh(['plan', 'planVariant', 'payments']);
                }
            }

            $this->createPayment($subscription, [
                'amount' => $subscription->planVariant->amount,
                'currency' => $subscription->planVariant->currency->value,
                'status' => PaymentStatus::FAILED,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'processed_at' => now(),
            ]);

            if ($subscription->status !== SubscriptionStatus::PAST_DUE) {
                $subscription = $this->lifecycleService->moveToPastDue($subscription);
            }

            DB::commit();

            return $subscription->load(['plan', 'planVariant', 'payments']);
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function ensureSuccessAllowed(Subscription $subscription): void
    {
        if ($subscription->status === SubscriptionStatus::CANCELED) {
            throw ValidationException::withMessages([
                'subscription' => ['Cannot pay for a canceled subscription.'],
            ]);
        }


        if (
            $subscription->status === SubscriptionStatus::ACTIVE &&
            $subscription->current_period_ends_at &&
            $subscription->current_period_ends_at->isFuture()
        ) {
            throw ValidationException::withMessages([
                'subscription' => ['Subscription already active for current period.'],
            ]);
        }
    }

    private function ensureFailureAllowed(Subscription $subscription): void
    {
        if ($subscription->status === SubscriptionStatus::CANCELED) {
            throw ValidationException::withMessages([
                'subscription' => ['Cannot fail a canceled subscription.'],
            ]);
        }


        if (
            $subscription->status === SubscriptionStatus::ACTIVE &&
            $subscription->current_period_ends_at &&
            $subscription->current_period_ends_at->isFuture()
        ) {
            throw ValidationException::withMessages([
                'subscription' => ['Cannot fail payment before billing period ends.'],
            ]);
        }
    }

    private function createPayment(Subscription $subscription, array $data): void
    {
        $subscription->payments()->create($data);
    }
}
