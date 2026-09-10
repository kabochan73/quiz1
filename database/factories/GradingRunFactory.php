<?php

namespace Database\Factories;

use App\Enums\GradingRunStatus;
use App\Models\GradingRun;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradingRun>
 */
class GradingRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'status' => GradingRunStatus::Running,
            'question_count' => 1,
            'ai_model' => 'claude-sonnet-5',
            'prompt_version' => 'v1',
            'input_tokens' => null,
            'output_tokens' => null,
            'ai_raw_response' => null,
            'error_message' => null,
            'started_at' => now(),
            'finished_at' => null,
        ];
    }

    /**
     * 成功して終わった run。
     */
    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => GradingRunStatus::Succeeded,
            'input_tokens' => fake()->numberBetween(500, 3000),
            'output_tokens' => fake()->numberBetween(200, 1500),
            'finished_at' => now(),
        ]);
    }
}
