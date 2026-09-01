<?php

namespace Database\Factories;

use App\Models\QuestionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionType>
 */
class QuestionTypeFactory extends Factory
{
    protected $model = QuestionType::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
        ];
    }
}
