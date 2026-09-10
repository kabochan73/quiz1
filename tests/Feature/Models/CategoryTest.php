<?php

use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\QueryException;

// カテゴリの階層（隣接リスト）まわりの検証。

it('親と子のリレーションをたどれる', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->childOf($parent)->create();

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children->pluck('id'))->toContain($child->id);
});

it('isRoot() はトップレベルのときだけ true', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();

    expect($root->isRoot())->toBeTrue()
        ->and($child->isRoot())->toBeFalse();
});

it('childOf() で depth が親 + 1 になる', function () {
    $root = Category::factory()->create();               // depth 0
    $child = Category::factory()->childOf($root)->create();
    $grandchild = Category::factory()->childOf($child)->create();

    expect($root->depth)->toBe(0)
        ->and($child->depth)->toBe(1)
        ->and($grandchild->depth)->toBe(2);
});

it('親を削除すると子孫もまとめて削除される（cascade）', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();
    $grandchild = Category::factory()->childOf($child)->create();

    $root->delete();

    expect(Category::find($child->id))->toBeNull()
        ->and(Category::find($grandchild->id))->toBeNull();
});

it('children() は sort_order 昇順で返る', function () {
    $root = Category::factory()->create();
    $b = Category::factory()->childOf($root)->create(['sort_order' => 2]);
    $a = Category::factory()->childOf($root)->create(['sort_order' => 1]);

    expect($root->children->pluck('id')->all())->toBe([$a->id, $b->id]);
});

it('同じ親の下で slug が重複すると例外', function () {
    $user = User::factory()->create();
    $root = Category::factory()->for($user)->create();

    Category::factory()->for($user)->childOf($root)->create(['slug' => 'dup']);

    Category::factory()->for($user)->childOf($root)->create(['slug' => 'dup']);
})->throws(QueryException::class);

it('カテゴリに紐づくクイズを取得できる', function () {
    $category = Category::factory()->create();
    $quiz = Quiz::factory()->for($category)->for($category->user)->create();

    expect($category->quizzes->pluck('id'))->toContain($quiz->id);
});
