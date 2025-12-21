<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConcertProviderSyncLog>
 */
class ConcertProviderSyncLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prn_artist_id' => $this->faker->numberBetween(1, 9_999_999),
            'artist_id' => null,
            'provider' => $this->faker->randomElement(['ticketmaster', 'eventim']),
            'provider_artist_id' => $this->faker->optional()->uuid(),
            'ok' => true,
            'status_code' => 200,
            'result_count' => 1,
            'duration_ms' => $this->faker->numberBetween(50, 1_500),
            'error_message' => null,
            'response_payload' => json_encode(['ok' => true, 'events' => []], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }
}
