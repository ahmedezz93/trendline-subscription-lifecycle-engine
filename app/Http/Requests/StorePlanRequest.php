<?php

namespace App\Http\Requests;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:plans,name'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],

            'planVariants' => ['required', 'array', 'min:1'],
            'planVariants.*.billing_cycle' => ['required', new Enum(BillingCycle::class)],
            'planVariants.*.currency' => ['required', new Enum(Currency::class)],
            'planVariants.*.amount' => ['required', 'numeric', 'min:0'],
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
            'name.required' => 'Plan name is required.',
            'name.unique' => 'Plan name must be unique.',
            'trial_days.required' => 'Trial days is required.',
            'trial_days.integer' => 'Trial days must be an integer.',
            'trial_days.min' => 'Trial days cannot be less than 0.',
            'trial_days.max' => 'Trial days cannot exceed 365.',
            'is_active.boolean' => 'Is active must be true or false.',
            'planVariants.required' => 'At least one plan variant is required.',
            'planVariants.array' => 'Plan variants must be an array.',
            'planVariants.min' => 'At least one plan variant is required.',
            'planVariants.*.billing_cycle.required' => 'Billing cycle is required.',
            'planVariants.*.currency.required' => 'Currency is required.',
            'planVariants.*.amount.required' => 'Amount is required.',
            'planVariants.*.amount.numeric' => 'Amount must be a number.',
            'planVariants.*.amount.min' => 'Amount must be greater than 0.',
        ];
    }
}