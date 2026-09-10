<?php

namespace Database\Factories;

use App\Enums\ReferenceAnswerSource;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'title' => null,
            'body' => fake()->paragraph(),
            'reference_answer' => null,
            'reference_answer_source' => ReferenceAnswerSource::None,
            'reference_version' => 0,
            'difficulty' => fake()->numberBetween(1, 5),
            // 満点は 10 固定。ルーブリック観点の配点合計もこれに合わせる。
            'max_score' => 10,
            'sort_order' => 1,
        ];
    }

    /**
     * AI 生成済みの参考解答を持つ状態。
     */
    public function withReferenceAnswer(): static
    {
        return $this->state(fn () => [
            'reference_answer' => fake()->paragraph(),
            'reference_answer_source' => ReferenceAnswerSource::Ai,
            'reference_version' => 1,
        ]);
    }
}
