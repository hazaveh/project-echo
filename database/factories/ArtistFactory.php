<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Artist>
 */
class ArtistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prn_artist_id' => $this->faker->unique()->numberBetween(1, 9_999_999),
            'name' => $this->faker->words(2, true),
            'spotify' => $this->faker->optional()->regexify('[A-Za-z0-9]{22}'),
        ];
    }
}
