<?php

namespace Database\Factories;

use App\Models\CustomerSupplierAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerSupplierAccount>
 */
class CustomerSupplierAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => $this->faker->unique()->phoneNumber(),
            'dealer' => 0,
            'type' => 2,
            'email' => $this->faker->safeEmail(),
            'admin_id' => 1,
            'shop_id' => 1,
            'address' => $this->faker->streetAddress(),
            'location' => $this->faker->city(),
            'details' => $this->faker->sentence(),
            'created_at' => now()->toDateString(),
            'updated_at' => now(),
        ];
    }

    public function supplier(): static
    {
        return $this->state(fn (): array => [
            'dealer' => 1,
            'type' => 1,
        ]);
    }
}
