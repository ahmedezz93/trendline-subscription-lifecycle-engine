<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'billing_cycle' => $this->billing_cycle?->value,
            'currency' => $this->currency?->value,
            'amount' => (float) $this->amount,
        ];
    }
}
