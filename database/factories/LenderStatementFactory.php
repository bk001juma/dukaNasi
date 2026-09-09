<?php

namespace Database\Factories;

use App\Models\LenderStatement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LenderStatement>
 */
class LenderStatementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lender_id' => 1,
            'type' => 0,
            'is_collection' => 0,
            'paydesc' => 'Inventory',
            'details' => 'Credit Sale',
            'amount' => '-1000.0',
            'date' => now()->toDateString(),
            'admin_id' => 1,
            'shop_id' => 1,
            'user_id' => 1,
            'created_at' => now(),
            'reference' => (string) $this->faker->randomNumber(6),
        ];
    }
}
