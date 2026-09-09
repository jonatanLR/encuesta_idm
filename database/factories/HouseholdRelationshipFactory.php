<?php

namespace Database\Factories;

use App\Models\HouseholdRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseholdRelationship>
 */
class HouseholdRelationshipFactory extends Factory
{
    protected $model = HouseholdRelationship::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('REL_####'),
            'name' => fake()->words(2, true),
            'active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
