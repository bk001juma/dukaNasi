<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst($this->faker->unique()->words(2, true)),
            'amount' => $this->faker->numberBetween(10000, 100000),
            'description' => $this->faker->sentence(),
            'duration_days' => SubscriptionPlan::DURATION_DAYS,
        ];
    }
}
