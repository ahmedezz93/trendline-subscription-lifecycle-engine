<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPaymentFailureRequest;
use App\Http\Requests\RecordPaymentSuccessRequest;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\PaymentService;
use App\Services\SubscriptionLifecycleService;
use App\Services\SubscriptionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly PaymentService $paymentService,
        private readonly SubscriptionLifecycleService $lifecycleService,
    ) {}

    public function index(): JsonResponse
    {
        $subscriptions = Subscription::with(['plan', 'planVariant', 'payments'])->latest()->paginate();

        return $this->successResponse(SubscriptionResource::collection($subscriptions), 'Subscriptions fetched successfully.');
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->create($request->validated());

        return $this->successResponse(new SubscriptionResource($subscription->load(['plan', 'planVariant', 'payments'])), 'Subscription created successfully.');
    }

    public function show(Subscription $subscription): JsonResponse
    {
        return $this->successResponse(new SubscriptionResource($subscription->load(['plan', 'planVariant', 'payments'])), 'Subscription fetched successfully.');
    }

    public function access(int $id): JsonResponse
    {
        $subscription = Subscription::find($id);

        if (!$subscription) {
            throw ValidationException::withMessages([
                'subscription_id' => ['Subscription not found.'],
            ]);
        }

        return $this->successResponse([
            'subscription_id' => $subscription->id,
            'status' => $subscription->status->value,
            'has_access' => $subscription->hasAccess(),
        ], 'Subscription access fetched successfully.');
    }

    public function paymentSuccess(RecordPaymentSuccessRequest $request, $id): JsonResponse
    {
        $subscription = $this->paymentService->recordSuccess($id, $request->validated());

        return $this->successResponse(
            new SubscriptionResource($subscription->load(['plan', 'planVariant', 'payments'])),
            'Payment recorded successfully.'
        );
    }

    public function paymentFailed($id)
    {
        $subscription = $this->paymentService->recordFailure($id);

        return $this->successResponse(
            new SubscriptionResource($subscription->load(['plan', 'planVariant', 'payments'])),
            'Payment failure recorded successfully.'
        );
    }

    public function cancel($id)
    {
        $subscription = $this->lifecycleService->cancel($id);

        return $this->successResponse(
            new SubscriptionResource($subscription->load(['plan', 'planVariant', 'payments'])),
            'Subscription canceled successfully.'
        );
    }
}
