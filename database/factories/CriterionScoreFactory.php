<?php

namespace Database\Factories;

use App\Models\CriterionScore;
use App\Models\QuestionAttempt;
use App\Models\RubricCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CriterionScore>
 */
class CriterionScoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $max = 5;

        return [
            'question_attempt_id' => QuestionAttempt::factory(),
            'rubric_criterion_id' => RubricCriterion::factory(),
            'criterion_title' => rtrim(fake()->sentence(3), '.'),
            'awarded_points' => fake()->numberBetween(0, $max),
            'max_points' => $max,
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
