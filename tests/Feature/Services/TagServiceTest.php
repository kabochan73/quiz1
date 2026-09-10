<?php

use App\Models\Tag;
use App\Models\User;
use App\Services\Tag\TagService;

beforeEach(function () {
    $this->service = app(TagService::class);
    $this->user = User::factory()->create();
});

it('新しいタグ名は作成して id を返す', function () {
    $ids = $this->service->resolveIds($this->user, ['頻出', '苦手']);

    expect($ids)->toHaveCount(2)
        ->and(Tag::whereIn('id', $ids)->pluck('name')->all())
        ->toEqualCanonicalizing(['頻出', '苦手']);
});

it('既存のタグは再利用する（重複作成しない）', function () {
    $existing = Tag::factory()->for($this->user)->create(['name' => '頻出']);

    $ids = $this->service->resolveIds($this->user, ['頻出', '新規']);

    expect($ids)->toContain($existing->id)
        ->and(Tag::where('user_id', $this->user->id)->count())->toBe(2);
});

it('前後の空白を除去し、空文字と重複を捨てる', function () {
    $ids = $this->service->resolveIds($this->user, ['  タグ  ', 'タグ', '', '   ']);

    expect($ids)->toHaveCount(1)
        ->and(Tag::find($ids[0])->name)->toBe('タグ');
});

it('日本語名でも slug が空にならず、ユーザー内で一意', function () {
    $this->service->resolveIds($this->user, ['あ', 'い']);

    $slugs = Tag::where('user_id', $this->user->id)->pluck('slug');

    expect($slugs)->each->toStartWith('tag-')
        ->and($slugs->unique())->toHaveCount(2);
});

it('別ユーザーの同名タグは別物として扱う', function () {
    $other = User::factory()->create();
    Tag::factory()->for($other)->create(['name' => '共有名']);

    $ids = $this->service->resolveIds($this->user, ['共有名']);

    expect(Tag::find($ids[0])->user_id)->toBe($this->user->id);
});
