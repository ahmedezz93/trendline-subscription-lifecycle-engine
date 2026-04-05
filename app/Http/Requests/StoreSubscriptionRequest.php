<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'plan_variant_id' => [
                'required',
                'integer',
                'exists:plan_variants,id',
                Rule::exists('plan_variants', 'id')->where(fn ($query) => $query->where('plan_id', $this->input('plan_id'))),
            ],
        ];
    }
}
