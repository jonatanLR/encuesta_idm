<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'question_type_id' => QuestionType::factory(),
            'data_source' => null,
            'data_source_table' => null,
            'code' => fake()->unique()->bothify('TEST_###'),
            'label' => fake()->sentence(),
            'description' => null,
            'required' => false,
            'active' => true,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}