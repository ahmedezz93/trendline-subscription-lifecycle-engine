<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status?->value,
            'has_access' => $this->has_access,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'plan_variant' => new PlanVariantResource($this->whenLoaded('planVariant')),
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'trial_ends_at' => $this->trial_ends_at?->toDateTimeString(),
            'current_period_starts_at' => $this->current_period_starts_at?->toDateTimeString(),
            'current_period_ends_at' => $this->current_period_ends_at?->toDateTimeString(),
            'grace_period_ends_at' => $this->grace_period_ends_at?->toDateTimeString(),
            'canceled_at' => $this->canceled_at?->toDateTimeString(),
            'payments' => SubscriptionPaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}