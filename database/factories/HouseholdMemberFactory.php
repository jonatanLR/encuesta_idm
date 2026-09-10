<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HouseholdRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HouseholdMember>
 */
class HouseholdMemberFactory extends Factory
{
    protected $model = HouseholdMember::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'household_id' => Household::factory(),
            'relationship_id' => HouseholdRelationship::factory(),
            'name' => fake()->name(),
            'age' => fake()->randomFloat(2, 0, 90),
            'sex' => null,
            'dni' => null,
        ];
    }
}
