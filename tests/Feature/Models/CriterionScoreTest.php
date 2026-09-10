<?php

use App\Models\CriterionScore;
use App\Models\QuestionAttempt;
use App\Models\RubricCriterion;

it('questionAttempt / rubricCriterion をたどれる', function () {
    $qa = QuestionAttempt::factory()->create();
    $criterion = RubricCriterion::factory()->create();

    $score = CriterionScore::factory()
        ->for($qa)
        ->for($criterion, 'rubricCriterion')
        ->create();

    expect($score->questionAttempt->is($qa))->toBeTrue()
        ->and($score->rubricCriterion->is($criterion))->toBeTrue();
});

it('元の観点を削除しても採点内訳は残り、rubric_criterion_id が null になる', function () {
    $criterion = RubricCriterion::factory()->create();
    $score = CriterionScore::factory()->for($criterion, 'rubricCriterion')->create();

    $criterion->delete();

    expect($score->fresh())->not->toBeNull()
        ->and($score->fresh()->rubric_criterion_id)->toBeNull();
});

it('回答（question_attempt）を削除すると採点内訳も削除される（cascade）', function () {
    $score = CriterionScore::factory()->create();

    $score->questionAttempt->delete();

    expect(CriterionScore::find($score->id))->toBeNull();
});
