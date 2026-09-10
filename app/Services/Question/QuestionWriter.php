<?php

namespace App\Services\Question;

use App\Models\Question;
use App\Models\Quiz;
use App\Services\Quiz\QuizCounters;
use Illuminate\Support\Facades\DB;

/**
 * 問題（と、そのルーブリック観点）の書き込みをまとめたサービス。
 *
 * 問題を変更するたびに:
 *   - 観点は「送られてきた内容で総入れ替え」する
 *     （criterion_scores.rubric_criterion_id は nullable なので、過去の採点内訳は壊れない）
 *   - クイズの非正規化カラム（question_count / total_max_score）を再計算する
 *   - その結果クイズが公開条件（1〜30問）を満たさなくなったら、公開を解除する
 */
class QuestionWriter
{
    public function __construct(private readonly QuizCounters $counters) {}

    /**
     * 新しい問題を作る。
     *
     * @param  array{title: ?string, body: string, difficulty: int, max_score: int, criteria: list<array{title: string, description: string, points: int, sort_order: int}>}  $data
     */
    public function create(Quiz $quiz, array $data): Question
    {
        return DB::transaction(function () use ($quiz, $data) {
            $question = $quiz->questions()->create([
                'title' => $data['title'],
                'body' => $data['body'],
                'difficulty' => $data['difficulty'],
                'max_score' => $data['max_score'],
                'sort_order' => (int) $quiz->questions()->max('sort_order') + 1,
            ]);

            $question->rubricCriteria()->createMany($data['criteria']);

            $this->syncQuiz($quiz->fresh());

            return $question;
        });
    }

    /**
     * 問題を更新し、観点を入れ替える。
     *
     * @param  array{title: ?string, body: string, difficulty: int, max_score: int, criteria: list<array{title: string, description: string, points: int, sort_order: int}>}  $data
     */
    public function update(Question $question, array $data): Question
    {
        return DB::transaction(function () use ($question, $data) {
            $question->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'difficulty' => $data['difficulty'],
                'max_score' => $data['max_score'],
            ]);

            // 観点は総入れ替え（過去の採点内訳は criterion_scores 側に残る）。
            $question->rubricCriteria()->delete();
            $question->rubricCriteria()->createMany($data['criteria']);

            $this->syncQuiz($question->quiz);

            return $question;
        });
    }

    /**
     * 問題を削除する。
     */
    public function delete(Question $question): void
    {
        DB::transaction(function () use ($question) {
            $quiz = $question->quiz;
            $question->delete();
            $this->syncQuiz($quiz);
        });
    }

    /**
     * クイズのカウンタを更新し、公開条件を満たさなくなっていたら非公開に戻す。
     */
    private function syncQuiz(Quiz $quiz): void
    {
        $this->counters->sync($quiz);

        if ($quiz->is_published && ! $quiz->hasPublishableQuestionCount()) {
            $quiz->update(['is_published' => false, 'published_at' => null]);
        }
    }
}
