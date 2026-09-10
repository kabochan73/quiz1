<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\RubricCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RubricCriterion>
 */
class RubricCriterionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'title' => rtrim(fake()->sentence(3), '.'),
            'description' => fake()->sentence(10),
            // 配点。問題の max_score（既定10）と観点数に応じて seeder / テスト側で調整する想定。
            'points' => 5,
            'sort_order' => 1,
        ];
    }
}
