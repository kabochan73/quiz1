<?php

use App\Models\Question;
use App\Models\Quiz;
use App\Services\Quiz\QuizCounters;

beforeEach(function () {
    $this->counters = app(QuizCounters::class);
});

it('問題の件数と満点合計をクイズに反映する', function () {
    $quiz = Quiz::factory()->create([
        'question_count' => 0,
        'total_max_score' => 0,
    ]);
    Question::factory()->for($quiz)->create(['max_score' => 10]);
    Question::factory()->for($quiz)->create(['max_score' => 25]);

    $this->counters->sync($quiz);

    expect($quiz->fresh())
        ->question_count->toBe(2)
        ->total_max_score->toBe(35);
});

it('問題が0件なら両方 0 になる', function () {
    // ズレた状態からでも正しく 0 に戻ることを確認する。
    $quiz = Quiz::factory()->create([
        'question_count' => 99,
        'total_max_score' => 999,
    ]);

    $this->counters->sync($quiz);

    expect($quiz->fresh())
        ->question_count->toBe(0)
        ->total_max_score->toBe(0);
});

it('問題を削除したあとに呼ぶとカウンタが減る', function () {
    $quiz = Quiz::factory()->create();
    $keep = Question::factory()->for($quiz)->create(['max_score' => 10]);
    $remove = Question::factory()->for($quiz)->create(['max_score' => 40]);

    $this->counters->sync($quiz);
    expect($quiz->fresh())->question_count->toBe(2)->total_max_score->toBe(50);

    $remove->delete();
    $this->counters->sync($quiz);

    expect($quiz->fresh())->question_count->toBe(1)->total_max_score->toBe(10);
});

it('同じクイズのインスタンスを返す', function () {
    $quiz = Quiz::factory()->create();

    expect($this->counters->sync($quiz))->toBe($quiz);
});
