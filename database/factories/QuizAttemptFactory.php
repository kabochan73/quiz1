<?php

namespace Database\Factories;

use App\Enums\QuizAttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'status' => QuizAttemptStatus::Grading,
            'total_score' => null,
            'max_score' => 10,
            'question_count' => 1,
            'submitted_at' => now(),
            'graded_at' => null,
        ];
    }

    /**
     * 採点完了済み。合計点を指定できる。
     */
    public function graded(int $totalScore = 8): static
    {
        return $this->state(fn () => [
            'status' => QuizAttemptStatus::Graded,
            'total_score' => $totalScore,
            'graded_at' => now(),
        ]);
    }
}
