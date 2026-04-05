<?php

namespace App\Http\Requests;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');
        $planId = is_object($plan) ? $plan->id : $plan;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name')->ignore($planId),
            ],
            'trial_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],

            'planVariants' => ['sometimes', 'array', 'min:1'],

            'planVariants.*.billing_cycle' => [
                'required_with:planVariants',
                new Enum(BillingCycle::class),
            ],

            'planVariants.*.currency' => [
                'required_with:planVariants',
                new Enum(Currency::class),
            ],

            'planVariants.*.amount' => [
                'required_with:planVariants',
                'numeric',
                'min:0.01',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            $planVariants = $this->input('planVariants');

            if (! is_array($planVariants)) {
                return;
            }

            $combinations = [];

            foreach ($planVariants as $index => $variant) {
                $billingCycle = $variant['billing_cycle'] ?? null;
                $currency = $variant['currency'] ?? null;

                if (! $billingCycle || ! $currency) {
                    continue;
                }

                $key = $billingCycle . '|' . $currency;

                if (isset($combinations[$key])) {
                    $validator->errors()->add(
                        "planVariants.$index.currency",
                        'Duplicate plan variant for the same billing cycle and currency.'
                    );
                } else {
                    $combinations[$key] = true;
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'planVariants.array' => 'Plan variants must be an array.',
            'planVariants.min' => 'At least one plan variant is required.',
            'planVariants.*.billing_cycle.required_with' => 'Billing cycle is required.',
            'planVariants.*.currency.required_with' => 'Currency is required.',
            'planVariants.*.amount.required_with' => 'Amount is required.',
            'planVariants.*.amount.numeric' => 'Amount must be a number.',
            'planVariants.*.amount.min' => 'Amount must be greater than 0.',
        ];
    }
}
