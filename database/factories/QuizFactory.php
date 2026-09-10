<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'title' => rtrim(fake()->sentence(4), '.'),
            'description' => fake()->optional()->paragraph(),
            // question_count / total_max_score は問題を足したときにアプリ側で再計算する。
            // ファクトリ単体では 0 のまま（seeder / テストで問題を作ってから更新する）。
            'question_count' => 0,
            'total_max_score' => 0,
            'is_published' => false,
            'published_at' => null,
        ];
    }

    /**
     * 公開済みクイズ。
     */
    public function published(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
