<?php

use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->other = User::factory()->create();
    $this->tag = Tag::factory()->for($this->owner)->create();
});

it('所有者は自分のタグを更新・削除できる', function () {
    expect($this->owner->can('update', $this->tag))->toBeTrue()
        ->and($this->owner->can('delete', $this->tag))->toBeTrue();
});

it('他人のタグは更新・削除できない', function () {
    expect($this->other->can('update', $this->tag))->toBeFalse()
        ->and($this->other->can('delete', $this->tag))->toBeFalse();
});
