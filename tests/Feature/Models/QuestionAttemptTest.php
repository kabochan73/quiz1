<?php

use App\Enums\QuestionAttemptStatus;
use App\Models\CriterionScore;
use App\Models\GradingRun;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\QuizAttempt;
use Illuminate\Database\QueryException;

it('quizAttempt / question / gradingRun / criterionScores をたどれる', function () {
    $quizAttempt = QuizAttempt::factory()->create();
    $question = Question::factory()->create();
    $run = GradingRun::factory()->for($quizAttempt)->create();

    $qa = QuestionAttempt::factory()
        ->for($quizAttempt)
        ->for($question)
        ->for($run, 'gradingRun')
        ->create();

    $score = CriterionScore::factory()->for($qa)->create();

    expect($qa->quizAttempt->is($quizAttempt))->toBeTrue()
        ->and($qa->question->is($question))->toBeTrue()
        ->and($qa->gradingRun->is($run))->toBeTrue()
        ->and($qa->criterionScores->pluck('id'))->toContain($score->id);
});

it('status は Enum、既定は pending', function () {
    $pending = QuestionAttempt::factory()->create();
    expect($pending->status)->toBe(QuestionAttemptStatus::Pending)
        ->and($pending->status->isFinished())->toBeFalse();

    $graded = QuestionAttempt::factory()->graded()->create();
    expect($graded->status)->toBe(QuestionAttemptStatus::Graded)
        ->and($graded->status->isFinished())->toBeTrue();
});

it('未採点なら gradingRun は null', function () {
    expect(QuestionAttempt::factory()->create()->gradingRun)->toBeNull();
});

it('同じ受験で同じ問題への回答は1つだけ（unique 制約）', function () {
    $quizAttempt = QuizAttempt::factory()->create();
    $question = Question::factory()->create();

    QuestionAttempt::factory()->for($quizAttempt)->for($question)->create();
    QuestionAttempt::factory()->for($quizAttempt)->for($question)->create();
})->throws(QueryException::class);

it('grading_run を削除しても回答は残り、grading_run_id が null になる', function () {
    $run = GradingRun::factory()->create();
    $qa = QuestionAttempt::factory()->for($run, 'gradingRun')->create();

    $run->delete();

    expect($qa->fresh())->not->toBeNull()
        ->and($qa->fresh()->grading_run_id)->toBeNull();
});
