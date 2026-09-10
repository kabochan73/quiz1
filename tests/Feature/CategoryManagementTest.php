<?php

use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Category\CategoryService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = app(CategoryService::class);
});

it('未ログインだとカテゴリ一覧はログイン画面へ', function () {
    $this->get(route('categories.index'))->assertRedirect(route('login'));
});

it('一覧には自分のカテゴリだけが出る', function () {
    $mine = Category::factory()->for($this->user)->create(['name' => '自分のカテゴリ']);
    $others = Category::factory()->create(['name' => '他人のカテゴリ']);

    $this->actingAs($this->user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertSee('自分のカテゴリ')
        ->assertDontSee('他人のカテゴリ');
});

it('トップレベルのカテゴリを作成できる', function () {
    $this->actingAs($this->user)
        ->post(route('categories.store'), ['name' => '英語', 'parent_id' => ''])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'user_id' => $this->user->id,
        'name' => '英語',
        'parent_id' => null,
        'depth' => 0,
    ]);
});

it('親を指定して子カテゴリを作成できる', function () {
    $parent = $this->service->create($this->user, 'IT資格', null);

    $this->actingAs($this->user)
        ->post(route('categories.store'), ['name' => '基本情報', 'parent_id' => $parent->id])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => '基本情報',
        'parent_id' => $parent->id,
        'depth' => 1,
    ]);
});

it('カテゴリ名は必須', function () {
    $this->actingAs($this->user)
        ->post(route('categories.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('同じ親の下に同名カテゴリは作れない', function () {
    $this->service->create($this->user, '重複', null);

    $this->actingAs($this->user)
        ->post(route('categories.store'), ['name' => '重複', 'parent_id' => ''])
        ->assertSessionHasErrors('name');
});

it('4階層目は作れない', function () {
    $l0 = $this->service->create($this->user, 'L0', null);
    $l1 = $this->service->create($this->user, 'L1', $l0);
    $l2 = $this->service->create($this->user, 'L2', $l1); // depth 2（3階層目）

    $this->actingAs($this->user)
        ->post(route('categories.store'), ['name' => 'L3', 'parent_id' => $l2->id])
        ->assertSessionHasErrors('parent_id');
});

it('他人の親カテゴリは指定できない', function () {
    $othersParent = Category::factory()->create();

    $this->actingAs($this->user)
        ->post(route('categories.store'), ['name' => 'x', 'parent_id' => $othersParent->id])
        ->assertSessionHasErrors('parent_id');
});

it('カテゴリ名と親を更新できる', function () {
    $parent = $this->service->create($this->user, 'parent', null);
    $target = $this->service->create($this->user, 'old', null);

    $this->actingAs($this->user)
        ->put(route('categories.update', $target), ['name' => 'new', 'parent_id' => $parent->id])
        ->assertRedirect(route('categories.index'));

    expect($target->fresh())
        ->name->toBe('new')
        ->parent_id->toBe($parent->id)
        ->depth->toBe(1);
});

it('自分の子孫を親には指定できない（循環禁止）', function () {
    $root = $this->service->create($this->user, 'root', null);
    $child = $this->service->create($this->user, 'child', $root);

    $this->actingAs($this->user)
        ->put(route('categories.update', $root), ['name' => 'root', 'parent_id' => $child->id])
        ->assertSessionHasErrors('parent_id');
});

it('カテゴリを削除すると子孫も消え、クイズは未分類になる', function () {
    $root = $this->service->create($this->user, 'root', null);
    $child = $this->service->create($this->user, 'child', $root);
    $quiz = Quiz::factory()->for($this->user)->for($child)->create();

    $this->actingAs($this->user)
        ->delete(route('categories.destroy', $root))
        ->assertRedirect(route('categories.index'));

    expect(Category::find($root->id))->toBeNull()
        ->and(Category::find($child->id))->toBeNull()
        ->and($quiz->fresh()->category_id)->toBeNull();
});

it('他人のカテゴリは編集・更新・削除できない（403）', function () {
    $others = Category::factory()->create();

    $this->actingAs($this->user)->get(route('categories.edit', $others))->assertForbidden();
    $this->actingAs($this->user)->put(route('categories.update', $others), ['name' => 'x'])->assertForbidden();
    $this->actingAs($this->user)->delete(route('categories.destroy', $others))->assertForbidden();
});
