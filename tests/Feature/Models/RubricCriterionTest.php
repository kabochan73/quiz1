<?php

use App\Models\CriterionScore;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\RubricCriterion;

it('rubric_criteria テーブルを使う', function () {
    expect((new RubricCriterion)->getTable())->toBe('rubric_criteria');
});

it('question / scores をたどれる', function () {
    $question = Question::factory()->create();
    $criterion = RubricCriterion::factory()->for($question)->create();

    $score = CriterionScore::factory()
        ->for($criterion, 'rubricCriterion')
        ->for(QuestionAttempt::factory())
        ->create();

    expect($criterion->question->is($question))->toBeTrue()
        ->and($criterion->scores->pluck('id'))->toContain($score->id);
});

it('問題を削除すると観点も削除される（cascade）', function () {
    $criterion = RubricCriterion::factory()->create();

    $criterion->question->delete();

    expect(RubricCriterion::find($criterion->id))->toBeNull();
});
