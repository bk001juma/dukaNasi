<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => (string) $this->faker->randomNumber(6),
            'gl_type' => 'Income',
            'open_balance' => 0,
            'balance' => 1000,
            'date' => now()->format('Y-m-d H:i:s'),
            'amount' => 1000,
            'crdr' => 'CR',
            'shop_id' => 1,
            'details' => 'Cash Payment',
            'user_id' => 1,
            'admin_id' => 1,
            'created_at' => now(),
            'name' => 'Cash',
            'type' => 'Cash',
            'acc_type' => 'Sale',
        ];
    }
}
