<?php

namespace Database\Factories;

use App\Models\RateLimitBlockLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RateLimitBlockLog>
 */
class RateLimitBlockLogFactory extends Factory
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
            'key_value' => $this->faker->userName(),
            'penalty_level' => 1,
            'blocked_until' => now()->addMinute()->getTimestamp(),
            'reason' => 'Exceeded rate limit',
            'timestamp' => now(),
        ];
    }
}
