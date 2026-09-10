<?php

use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Tag;
use App\Models\User;
use Carbon\CarbonInterface;

it('quizzes テーブルを使う', function () {
    expect((new Quiz)->getTable())->toBe('quizzes');
});

it('user / category / tags / questions / attempts をたどれる', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $quiz = Quiz::factory()->for($user)->for($category)->create();

    $tag = Tag::factory()->for($user)->create();
    $quiz->tags()->attach($tag);

    $question = Question::factory()->for($quiz)->create();
    $attempt = QuizAttempt::factory()->for($user)->for($quiz)->create();

    expect($quiz->user->is($user))->toBeTrue()
        ->and($quiz->category->is($category))->toBeTrue()
        ->and($quiz->tags->pluck('id'))->toContain($tag->id)
        ->and($quiz->questions->pluck('id'))->toContain($question->id)
        ->and($quiz->attempts->pluck('id'))->toContain($attempt->id);
});

it('is_published は bool、published_at は Carbon にキャストされる', function () {
    $quiz = Quiz::factory()->published()->create();

    expect($quiz->is_published)->toBeTrue()
        ->and($quiz->published_at)->toBeInstanceOf(CarbonInterface::class);

    expect(Quiz::factory()->create()->is_published)->toBeFalse();
});

it('questions() は sort_order 昇順で返る', function () {
    $quiz = Quiz::factory()->create();
    $q2 = Question::factory()->for($quiz)->create(['sort_order' => 2]);
    $q1 = Question::factory()->for($quiz)->create(['sort_order' => 1]);

    expect($quiz->questions->pluck('id')->all())->toBe([$q1->id, $q2->id]);
});

it('カテゴリを削除してもクイズは残り、category_id が null になる', function () {
    $category = Category::factory()->create();
    $quiz = Quiz::factory()->for($category)->for($category->user)->create();

    $category->delete();

    expect($quiz->fresh())->not->toBeNull()
        ->and($quiz->fresh()->category_id)->toBeNull();
});

it('ユーザーを削除するとクイズも削除される（cascade）', function () {
    $quiz = Quiz::factory()->create();

    $quiz->user->delete();

    expect(Quiz::find($quiz->id))->toBeNull();
});
