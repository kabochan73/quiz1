<?php

use App\Models\Quiz;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;

it('所有ユーザーをたどれる', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();

    expect($tag->user->is($user))->toBeTrue();
});

it('クイズと多対多（quiz_tag 経由）でつながる', function () {
    $tag = Tag::factory()->create();
    $quiz = Quiz::factory()->create();

    $quiz->tags()->attach($tag);

    expect($tag->quizzes->pluck('id'))->toContain($quiz->id)
        ->and($quiz->tags->pluck('id'))->toContain($tag->id);
});

it('同じユーザー内で slug が重複すると例外', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['slug' => 'dup']);
    Tag::factory()->for($user)->create(['slug' => 'dup']);
})->throws(QueryException::class);
