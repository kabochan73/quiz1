<?php

use App\Models\Category;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->other = User::factory()->create();
    $this->category = Category::factory()->for($this->owner)->create();
});

it('一覧と作成はログインユーザーなら誰でも可', function () {
    expect($this->other->can('viewAny', Category::class))->toBeTrue()
        ->and($this->other->can('create', Category::class))->toBeTrue();
});

it('所有者は自分のカテゴリを閲覧・更新・削除できる', function () {
    expect($this->owner->can('view', $this->category))->toBeTrue()
        ->and($this->owner->can('update', $this->category))->toBeTrue()
        ->and($this->owner->can('delete', $this->category))->toBeTrue();
});

it('他人のカテゴリは閲覧・更新・削除できない', function () {
    expect($this->other->can('view', $this->category))->toBeFalse()
        ->and($this->other->can('update', $this->category))->toBeFalse()
        ->and($this->other->can('delete', $this->category))->toBeFalse();
});
