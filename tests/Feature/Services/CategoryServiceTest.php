<?php

use App\Models\Category;
use App\Models\User;
use App\Services\Category\CategoryService;

beforeEach(function () {
    $this->service = app(CategoryService::class);
    $this->user = User::factory()->create();
});

it('トップレベルのカテゴリを depth 0 で作る', function () {
    $category = $this->service->create($this->user, '英語', null);

    expect($category->depth)->toBe(0)
        ->and($category->parent_id)->toBeNull()
        ->and($category->user_id)->toBe($this->user->id);
});

it('子カテゴリは親の depth + 1 になる', function () {
    $parent = $this->service->create($this->user, 'IT資格', null);
    $child = $this->service->create($this->user, '基本情報', $parent);

    expect($child->depth)->toBe(1)
        ->and($child->parent_id)->toBe($parent->id);
});

it('日本語名でも slug が空にならない', function () {
    $category = $this->service->create($this->user, '英作文', null);

    expect($category->slug)->not->toBe('')
        ->and($category->slug)->toStartWith('category-');
});

it('同じ親の下で slug が重複しそうなら接尾辞を付ける', function () {
    // Str::slug が同じ結果になる名前を2つ作る。
    $a = $this->service->create($this->user, 'Algorithm', null);
    $b = $this->service->create($this->user, 'algorithm', null);

    expect($a->slug)->toBe('algorithm')
        ->and($b->slug)->toBe('algorithm-2');
});

it('末尾に追加する sort_order を採番する', function () {
    $first = $this->service->create($this->user, 'A', null);
    $second = $this->service->create($this->user, 'B', null);

    expect($first->sort_order)->toBe(0)
        ->and($second->sort_order)->toBe(1);
});

it('親を付け替えると自分と子孫の depth が振り直される', function () {
    // root1 > mid > leaf （mid=1, leaf=2）
    $root1 = $this->service->create($this->user, 'root1', null);
    $mid = $this->service->create($this->user, 'mid', $root1);
    $leaf = $this->service->create($this->user, 'leaf', $mid);

    // mid をトップレベルへ移動 → mid=0, leaf=1
    $this->service->update($mid, 'mid', null);

    expect($mid->fresh()->depth)->toBe(0)
        ->and($leaf->fresh()->depth)->toBe(1);
});

it('descendantIds は全子孫を返す', function () {
    $root = $this->service->create($this->user, 'root', null);
    $child = $this->service->create($this->user, 'child', $root);
    $grandchild = $this->service->create($this->user, 'grandchild', $child);

    expect($this->service->descendantIds($root))
        ->toEqualCanonicalizing([$child->id, $grandchild->id]);
});

it('subtreeHeight は部分木の高さを返す', function () {
    $root = $this->service->create($this->user, 'root', null);
    $child = $this->service->create($this->user, 'child', $root);
    $this->service->create($this->user, 'grandchild', $child);

    expect($this->service->subtreeHeight($root))->toBe(2)
        ->and($this->service->subtreeHeight($child))->toBe(1)
        ->and($this->service->subtreeHeight($child->fresh()))->toBe(1);
});
