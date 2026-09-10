<?php

use App\Enums\ReferenceAnswerSource;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\Quiz;
use App\Models\RubricCriterion;

it('quiz / rubricCriteria / attempts をたどれる', function () {
    $quiz = Quiz::factory()->create();
    $question = Question::factory()->for($quiz)->create();
    $criterion = RubricCriterion::factory()->for($question)->create();
    $attempt = QuestionAttempt::factory()->for($question)->create();

    expect($question->quiz->is($quiz))->toBeTrue()
        ->and($question->rubricCriteria->pluck('id'))->toContain($criterion->id)
        ->and($question->attempts->pluck('id'))->toContain($attempt->id);
});

it('reference_answer_source は Enum にキャストされ、既定は None', function () {
    $question = Question::factory()->create();
    expect($question->reference_answer_source)->toBe(ReferenceAnswerSource::None);

    $withRef = Question::factory()->withReferenceAnswer()->create();
    expect($withRef->reference_answer_source)->toBe(ReferenceAnswerSource::Ai)
        ->and($withRef->reference_answer_source->exists())->toBeTrue();
});

it('displayTitle() は未設定なら「問N」を返す', function () {
    $noTitle = Question::factory()->create(['title' => null, 'sort_order' => 3]);
    $withTitle = Question::factory()->create(['title' => '独自タイトル']);

    expect($noTitle->displayTitle())->toBe('問3')
        ->and($withTitle->displayTitle())->toBe('独自タイトル');
});

it('rubricCriteria() は sort_order 昇順で返る', function () {
    $question = Question::factory()->create();
    $c2 = RubricCriterion::factory()->for($question)->create(['sort_order' => 2]);
    $c1 = RubricCriterion::factory()->for($question)->create(['sort_order' => 1]);

    expect($question->rubricCriteria->pluck('id')->all())->toBe([$c1->id, $c2->id]);
});

it('クイズを削除すると問題も削除される（cascade）', function () {
    $question = Question::factory()->create();

    $question->quiz->delete();

    expect(Question::find($question->id))->toBeNull();
});
