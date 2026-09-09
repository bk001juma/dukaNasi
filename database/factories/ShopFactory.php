<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'details' => $this->faker->sentence(),
            'capital' => '0',
            'equity' => '0',
            'rent' => '0',
            'admin_id' => 1,
            'address' => $this->faker->streetAddress(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'location' => $this->faker->city(),
            'category' => 'general',
            'type' => 1,
            'status' => 1,
            'license_assigned' => 1,
        ];
    }
}
