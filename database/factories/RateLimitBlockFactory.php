<?php

namespace Database\Factories;

use App\Models\RateLimitBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RateLimitBlock>
 */
class RateLimitBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key_type' => 'username',
            'key_value' => $this->faker->unique()->userName(),
            'blocked_until' => now()->addMinute()->getTimestamp(),
            'current_penalty' => 1,
            'created_at' => now(),
        ];
    }
}
