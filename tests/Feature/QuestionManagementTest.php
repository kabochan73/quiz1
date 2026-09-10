<?php

use App\Models\Question;
use App\Models\Quiz;
use App\Models\RubricCriterion;
use App\Models\User;
use App\Services\Quiz\QuizCounters;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->quiz = Quiz::factory()->for($this->user)->create();
});

/**
 * 有効な問題フォームの入力。criteria の配点合計 = 10。
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function questionPayload(array $overrides = []): array
{
    return array_merge([
        'title' => null,
        'body' => 'サンプル問題文',
        'difficulty' => 3,
        'criteria' => [
            ['title' => '観点1', 'description' => 'これが書けていれば加点', 'points' => 6],
            ['title' => '観点2', 'description' => 'これも書けていれば加点', 'points' => 4],
        ],
    ], $overrides);
}

it('問題を追加すると観点も保存され、満点は配点合計になる', function () {
    $this->actingAs($this->user)
        ->post(route('quizzes.questions.store', $this->quiz), questionPayload())
        ->assertRedirect(route('quizzes.show', $this->quiz));

    $question = $this->quiz->questions()->firstOrFail();

    expect($question->max_score)->toBe(10)
        ->and($question->sort_order)->toBe(1)
        ->and($question->rubricCriteria)->toHaveCount(2)
        ->and($this->quiz->fresh())
        ->question_count->toBe(1)
        ->total_max_score->toBe(10);
});

it('問題文は必須', function () {
    $this->actingAs($this->user)
        ->post(route('quizzes.questions.store', $this->quiz), questionPayload(['body' => '']))
        ->assertSessionHasErrors('body');
});

it('観点は最低1つ必要', function () {
    $this->actingAs($this->user)
        ->post(route('quizzes.questions.store', $this->quiz), questionPayload(['criteria' => []]))
        ->assertSessionHasErrors('criteria');
});

it('観点は最大10個まで', function () {
    $criteria = array_fill(0, 11, ['title' => 'x', 'description' => 'y', 'points' => 1]);

    $this->actingAs($this->user)
        ->post(route('quizzes.questions.store', $this->quiz), questionPayload(['criteria' => $criteria]))
        ->assertSessionHasErrors('criteria');
});

it('配点は1以上', function () {
    $this->actingAs($this->user)
        ->post(route('quizzes.questions.store', $this->quiz), questionPayload([
            'criteria' => [['title' => 'x', 'description' => 'y', 'points' => 0]],
        ]))
        ->assertSessionHasErrors('criteria.0.points');
});

it('30問に達していると追加できない', function () {
    $full = Quiz::factory()->for($this->user)->create(['question_count' => 30]);

    $this->actingAs($this->user)
        ->get(route('quizzes.questions.create', $full))
        ->assertRedirect(route('quizzes.show', $full));

    $this->actingAs($this->user)
        ->post(route('quizzes.questions.store', $full), questionPayload())
        ->assertRedirect(route('quizzes.show', $full));

    expect($full->questions()->count())->toBe(0);
});

it('他人のクイズには問題を追加できない（403）', function () {
    $others = Quiz::factory()->create();

    $this->actingAs($this->user)
        ->get(route('quizzes.questions.create', $others))->assertForbidden();
    $this->actingAs($this->user)
        ->post(route('quizzes.questions.store', $others), questionPayload())->assertForbidden();
});

it('問題を更新すると観点が総入れ替えされ、満点も再計算される', function () {
    $question = Question::factory()->for($this->quiz)->create(['max_score' => 10]);
    $old = RubricCriterion::factory()->for($question)->create(['points' => 10]);

    $this->actingAs($this->user)
        ->put(route('questions.update', $question), questionPayload([
            'body' => '更新後の問題文',
            'criteria' => [
                ['title' => '新観点A', 'description' => '説明', 'points' => 7],
                ['title' => '新観点B', 'description' => '説明', 'points' => 8],
            ],
        ]))
        ->assertRedirect(route('quizzes.show', $this->quiz));

    expect(RubricCriterion::find($old->id))->toBeNull()
        ->and($question->fresh())
        ->body->toBe('更新後の問題文')
        ->max_score->toBe(15)
        ->and($question->fresh()->rubricCriteria->pluck('title')->all())
        ->toBe(['新観点A', '新観点B']);
});

it('他人の問題は編集・更新・削除できない（403）', function () {
    $others = Question::factory()->create();

    $this->actingAs($this->user)->get(route('questions.edit', $others))->assertForbidden();
    $this->actingAs($this->user)->put(route('questions.update', $others), questionPayload())->assertForbidden();
    $this->actingAs($this->user)->delete(route('questions.destroy', $others))->assertForbidden();
});

it('最後の問題を削除すると、公開中のクイズは非公開に戻る', function () {
    $quiz = Quiz::factory()->for($this->user)->create();
    $question = Question::factory()->for($quiz)->create();
    // 公開状態にしておく
    app(QuizCounters::class)->sync($quiz);
    $quiz->update(['is_published' => true, 'published_at' => now()]);

    $this->actingAs($this->user)
        ->delete(route('questions.destroy', $question))
        ->assertRedirect(route('quizzes.show', $quiz));

    expect($quiz->fresh())
        ->question_count->toBe(0)
        ->is_published->toBeFalse();
});

it('問題を並べ替えられる', function () {
    $q1 = Question::factory()->for($this->quiz)->create(['sort_order' => 1]);
    $q2 = Question::factory()->for($this->quiz)->create(['sort_order' => 2]);
    $q3 = Question::factory()->for($this->quiz)->create(['sort_order' => 3]);

    $this->actingAs($this->user)
        ->patch(route('quizzes.questions.reorder', $this->quiz), ['ids' => [$q3->id, $q1->id, $q2->id]])
        ->assertRedirect(route('quizzes.show', $this->quiz));

    expect($q3->fresh()->sort_order)->toBe(1)
        ->and($q1->fresh()->sort_order)->toBe(2)
        ->and($q2->fresh()->sort_order)->toBe(3);
});

it('並べ替えに他クイズの問題 id を混ぜると弾かれる', function () {
    $mine = Question::factory()->for($this->quiz)->create();
    $foreign = Question::factory()->create();

    $this->actingAs($this->user)
        ->patch(route('quizzes.questions.reorder', $this->quiz), ['ids' => [$mine->id, $foreign->id]])
        ->assertSessionHasErrors('ids.1');
});
