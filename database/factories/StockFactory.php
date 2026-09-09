<?php

namespace Database\Factories;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'barcode' => $this->faker->ean13(),
            'invoice' => (string) $this->faker->randomNumber(6),
            'type' => 0,
            'mode' => 0,
            'name' => $this->faker->word(),
            'stock' => 10,
            'purchase_price' => 500,
            'sale_price' => 750,
            'min_stock' => 1,
            'unit' => 'pcs',
            'status' => 0,
            'app_updated_at' => now(),
            'app_id' => 0,
            'shop_id' => 1,
            'admin_id' => 1,
            'origin_stock' => 10,
            'category' => 'general',
        ];
    }
}
