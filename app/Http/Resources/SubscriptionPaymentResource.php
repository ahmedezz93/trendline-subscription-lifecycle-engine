<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency?->value,
            'status' => $this->status?->value,
            'transaction_reference' => $this->transaction_reference,
            'processed_at' => $this->processed_at,
        ];
    }
}
