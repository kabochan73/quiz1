<?php

namespace Database\Factories;

use App\Enums\QuestionAttemptStatus;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionAttempt>
 */
class QuestionAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'question_id' => Question::factory(),
            'grading_run_id' => null,
            'answer_text' => fake()->paragraph(),
            'status' => QuestionAttemptStatus::Pending,
            'score' => null,
            'max_score' => 10,
            'reference_version' => null,
            'overall_feedback' => null,
            'error_message' => null,
            'graded_at' => null,
        ];
    }

    /**
     * 採点済み。得点を指定できる。
     */
    public function graded(int $score = 8): static
    {
        return $this->state(fn () => [
            'status' => QuestionAttemptStatus::Graded,
            'score' => $score,
            'overall_feedback' => fake()->sentence(12),
            'graded_at' => now(),
        ]);
    }
}
