<?php

use App\Enums\GradingRunStatus;
use App\Models\GradingRun;
use App\Models\QuestionAttempt;
use App\Models\QuizAttempt;

it('quizAttempt / questionAttempts をたどれる', function () {
    $attempt = QuizAttempt::factory()->create();
    $run = GradingRun::factory()->for($attempt)->create();
    $qa = QuestionAttempt::factory()->for($run)->for($attempt)->create();

    expect($run->quizAttempt->is($attempt))->toBeTrue()
        ->and($run->questionAttempts->pluck('id'))->toContain($qa->id);
});

it('status は Enum にキャストされる', function () {
    $run = GradingRun::factory()->succeeded()->create();

    expect($run->status)->toBe(GradingRunStatus::Succeeded);
});

it('ai_raw_response は配列としてキャストされる', function () {
    $payload = ['results' => [['question_index' => 0, 'awarded_points' => 8]]];

    $run = GradingRun::factory()->create(['ai_raw_response' => $payload]);

    // DB に JSON で保存され、読み出し時に配列へ戻る。
    expect($run->fresh()->ai_raw_response)->toBe($payload);
});

it('受験を削除すると run も削除される（cascade）', function () {
    $run = GradingRun::factory()->create();

    $run->quizAttempt->delete();

    expect(GradingRun::find($run->id))->toBeNull();
});
