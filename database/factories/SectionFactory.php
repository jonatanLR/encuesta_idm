<?php

namespace Database\Factories;

use App\Models\Section;
use App\Models\SurveyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'survey_version_id' => SurveyVersion::factory(),
            'parent_id' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(1, 20),
            'active' => true,
        ];
    }
}
