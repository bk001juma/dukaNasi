<?php

namespace Database\Factories;

use App\Models\Shoping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shoping>
 */
class ShopingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 1,
            'stock_id' => 1,
            'invoice' => (string) $this->faker->randomNumber(6),
            'qty' => 1,
            'remaining_qty' => 9,
            'amount' => 750,
            'sale' => 750,
            'stock_name' => $this->faker->word(),
            'purchase_price' => 500,
            'total' => 750,
            'payment_mode' => 1,
            'user_id' => 1,
            'admin_id' => 1,
            'shop_id' => 1,
            'app_sync' => 1,
            'unit' => 'pcs',
        ];
    }
}
