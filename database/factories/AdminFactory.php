<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Support\LegacyPassword;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'password' => LegacyPassword::make('password'),
            'email' => $this->faker->unique()->safeEmail(),
            'active' => 1,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
            'joining_date' => now()->subMonth()->toDateString(),
            'expiring_date' => now()->addYear()->toDateString(),
            'user_type' => 'is_owner',
            'secret_key' => Str::random(43),
            'shops_ids' => '[]',
        ];
    }

    public function employee(int $adminId, array $shopIds, ?int $userId = null): static
    {
        return $this->state(fn (): array => [
            'admin_id' => $adminId,
            'user_id' => $userId ?? $this->faker->randomNumber(5),
            'user_type' => 'is_employee',
            'shops_ids' => json_encode(array_map('strval', $shopIds)),
        ]);
    }
}
