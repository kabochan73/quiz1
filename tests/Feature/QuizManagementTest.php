<?php

use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Tag;
use App\Models\User;
use App\Services\Quiz\QuizCounters;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('未ログインだとクイズ一覧はログイン画面へ', function () {
    $this->get(route('quizzes.index'))->assertRedirect(route('login'));
});

it('一覧には自分のクイズだけが出る', function () {
    Quiz::factory()->for($this->user)->create(['title' => '自分のクイズ']);
    Quiz::factory()->create(['title' => '他人のクイズ']);

    $this->actingAs($this->user)
        ->get(route('quizzes.index'))
        ->assertOk()
        ->assertSee('自分のクイズ')
        ->assertDontSee('他人のクイズ');
});

it('クイズを作成するとタグも作られ、詳細ページへ遷移する', function () {
    $category = Category::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)->post(route('quizzes.store'), [
        'title' => 'SQL 基礎',
        'description' => '出題範囲メモ',
        'category_id' => $category->id,
        'tags' => '頻出, 苦手',
    ]);

    $quiz = Quiz::where('title', 'SQL 基礎')->firstOrFail();

    $response->assertRedirect(route('quizzes.show', $quiz));
    expect($quiz->category_id)->toBe($category->id)
        ->and($quiz->tags->pluck('name')->all())->toEqualCanonicalizing(['頻出', '苦手'])
        ->and(Tag::where('user_id', $this->user->id)->count())->toBe(2);
});

it('タイトルは必須', function () {
    $this->actingAs($this->user)
        ->post(route('quizzes.store'), ['title' => ''])
        ->assertSessionHasErrors('title');
});

it('他人のカテゴリは指定できない', function () {
    $othersCategory = Category::factory()->create();

    $this->actingAs($this->user)
        ->post(route('quizzes.store'), ['title' => 'x', 'category_id' => $othersCategory->id])
        ->assertSessionHasErrors('category_id');
});

it('更新でタグを付け替えられる（外したタグは解除）', function () {
    $quiz = Quiz::factory()->for($this->user)->create();
    $quiz->tags()->attach(Tag::factory()->for($this->user)->create(['name' => '古いタグ']));

    $this->actingAs($this->user)
        ->put(route('quizzes.update', $quiz), ['title' => $quiz->title, 'tags' => '新しいタグ'])
        ->assertRedirect(route('quizzes.show', $quiz));

    expect($quiz->fresh()->tags->pluck('name')->all())->toBe(['新しいタグ']);
});

it('詳細・編集は他人だと 403', function () {
    $others = Quiz::factory()->create();

    $this->actingAs($this->user)->get(route('quizzes.show', $others))->assertForbidden();
    $this->actingAs($this->user)->get(route('quizzes.edit', $others))->assertForbidden();
    $this->actingAs($this->user)->put(route('quizzes.update', $others), ['title' => 'x'])->assertForbidden();
    $this->actingAs($this->user)->delete(route('quizzes.destroy', $others))->assertForbidden();
});

it('クイズを削除すると問題も消える', function () {
    $quiz = Quiz::factory()->for($this->user)->create();
    $question = Question::factory()->for($quiz)->create();

    $this->actingAs($this->user)
        ->delete(route('quizzes.destroy', $quiz))
        ->assertRedirect(route('quizzes.index'));

    expect(Quiz::find($quiz->id))->toBeNull()
        ->and(Question::find($question->id))->toBeNull();
});

it('問題が0問だと公開できない', function () {
    $quiz = Quiz::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->patch(route('quizzes.publish', $quiz))
        ->assertSessionHas('error');

    expect($quiz->fresh()->is_published)->toBeFalse();
});

it('問題が1問以上あれば公開でき、非公開にも戻せる', function () {
    $quiz = Quiz::factory()->for($this->user)->create();
    Question::factory()->for($quiz)->create();
    app(QuizCounters::class)->sync($quiz);

    $this->actingAs($this->user)->patch(route('quizzes.publish', $quiz));
    expect($quiz->fresh())->is_published->toBeTrue()
        ->and($quiz->fresh()->published_at)->not->toBeNull();

    $this->actingAs($this->user)->patch(route('quizzes.unpublish', $quiz));
    expect($quiz->fresh())->is_published->toBeFalse()
        ->and($quiz->fresh()->published_at)->toBeNull();
});

it('他人のクイズは公開操作できない（403）', function () {
    $others = Quiz::factory()->create();

    $this->actingAs($this->user)->patch(route('quizzes.publish', $others))->assertForbidden();
    $this->actingAs($this->user)->patch(route('quizzes.unpublish', $others))->assertForbidden();
});
