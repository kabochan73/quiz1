<?php

use App\Models\Quiz;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->other = User::factory()->create();
    $this->quiz = Quiz::factory()->for($this->owner)->create();
});

it('一覧と作成はログインユーザーなら可', function () {
    expect($this->other->can('viewAny', Quiz::class))->toBeTrue()
        ->and($this->other->can('create', Quiz::class))->toBeTrue();
});

it('所有者は自分のクイズを閲覧・更新・削除できる', function () {
    expect($this->owner->can('view', $this->quiz))->toBeTrue()
        ->and($this->owner->can('update', $this->quiz))->toBeTrue()
        ->and($this->owner->can('delete', $this->quiz))->toBeTrue();
});

it('他人のクイズは閲覧・更新・削除できない', function () {
    expect($this->other->can('view', $this->quiz))->toBeFalse()
        ->and($this->other->can('update', $this->quiz))->toBeFalse()
        ->and($this->other->can('delete', $this->quiz))->toBeFalse();
});
