<?php

namespace Database\Factories;

use App\Models\StockVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockVariant>
 */
class StockVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'default',
            'color' => 'default',
            'size' => 'default',
            'brand' => 'default',
            'purchase_price' => 500,
            'sale_price' => 750,
            'qty' => 10,
            'stock_id' => 1,
            'shop_id' => 1,
            'admin_id' => 1,
        ];
    }
}
