<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => mb_substr($this->faker->word(), 0, 16),
            'amount' => (string) $this->faker->numberBetween(1000, 9000),
            'details' => $this->faker->sentence(),
            'type' => 1,
            'user_id' => 1,
            'admin_id' => 1,
            'shop_id' => 1,
            'user_name' => $this->faker->name(),
            'date' => now()->toDateString(),
            'category' => 'general',
            'sync' => 1,
            'paid_from' => 'Cash',
        ];
    }
}
