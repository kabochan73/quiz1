<?php

use App\Enums\QuizAttemptStatus;
use App\Models\GradingRun;
use App\Models\QuestionAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\CarbonInterface;

it('user / quiz / questionAttempts / gradingRuns をたどれる', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->create();
    $attempt = QuizAttempt::factory()->for($user)->for($quiz)->create();

    $qa = QuestionAttempt::factory()->for($attempt)->create();
    $run = GradingRun::factory()->for($attempt)->create();

    expect($attempt->user->is($user))->toBeTrue()
        ->and($attempt->quiz->is($quiz))->toBeTrue()
        ->and($attempt->questionAttempts->pluck('id'))->toContain($qa->id)
        ->and($attempt->gradingRuns->pluck('id'))->toContain($run->id);
});

it('status は Enum、日時カラムは Carbon にキャストされる', function () {
    $attempt = QuizAttempt::factory()->graded()->create();

    expect($attempt->status)->toBe(QuizAttemptStatus::Graded)
        ->and($attempt->status->isComplete())->toBeTrue()
        ->and($attempt->submitted_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($attempt->graded_at)->toBeInstanceOf(CarbonInterface::class);
});

it('既定ステータスは grading', function () {
    expect(QuizAttempt::factory()->create()->status)->toBe(QuizAttemptStatus::Grading);
});

it('クイズを削除すると受験も削除される（cascade）', function () {
    $attempt = QuizAttempt::factory()->create();

    $attempt->quiz->delete();

    expect(QuizAttempt::find($attempt->id))->toBeNull();
});
