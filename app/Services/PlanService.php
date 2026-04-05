<?php

namespace App\Services;

use App\Models\Plan;
use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public function create(array $data): Plan
    {
        DB::beginTransaction();

        try {
            $planData = Arr::except($data, ['planVariants']);
            $planVariants = $data['planVariants'] ?? [];

            $plan = Plan::create($planData);

            if (! empty($planVariants)) {
                $plan->planVariants()->createMany($planVariants);
            }

            DB::commit();

            return $plan->load('planVariants');
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Plan $plan, array $data): Plan
    {
        DB::beginTransaction();

        try {
            $planData = Arr::except($data, ['planVariants']);
            $planVariants = $data['planVariants'] ?? null;

            $plan->update($planData);

            if (is_array($planVariants)) {
                $plan->planVariants()->delete();

                if (! empty($planVariants)) {
                    $plan->planVariants()->createMany($planVariants);
                }
            }

            DB::commit();

            return $plan->fresh('planVariants');
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Plan $plan): void
    {
        $plan->delete();
    }
}