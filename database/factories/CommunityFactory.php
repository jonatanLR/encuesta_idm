<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Community>
 */
class CommunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'municipality_id' => Municipality::factory(),
            'source_code' => fake()->unique()->numerify('########'),
            'name' => fake()->unique()->streetName(),
            'search_name' => fake()->unique()->streetName(),
            'type' => 'colony',
            'area' => null,
            'active' => true,
        ];
    }
}
