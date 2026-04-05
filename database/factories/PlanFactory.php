<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'code' => Str::lower(fake()->unique()->lexify('plan-?????')),
            'description' => fake()->sentence(),
            'trial_days' => 0,
            'is_active' => true,
        ];
    }
}
