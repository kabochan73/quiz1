<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * カテゴリの階層まわりの操作をまとめたサービス。
 *
 * 階層は「隣接リスト」（parent_id を持つだけ）で、次の不変条件をアプリ側で守る:
 *   - 深さは最大3階層（depth 0/1/2）。MAX_DEPTH = 2
 *   - 循環しない（親に自分自身や自分の子孫を指定できない）
 *   - depth は「親の depth + 1」。親を付け替えたら子孫ぶんも再計算する
 *   - slug は同じ親の兄弟の中で一意
 *
 * バリデーション（親が自分のものか、深さに収まるか等）は FormRequest 側で行い、
 * ここでは「正しい入力が来た前提」で保存処理を担う。
 */
class CategoryService
{
    /** 許容する最大の depth（0 起点なので 2 = 3階層）。 */
    public const int MAX_DEPTH = 2;

    /**
     * カテゴリを新規作成する。
     */
    public function create(User $user, string $name, ?Category $parent): Category
    {
        return Category::create([
            'user_id' => $user->id,
            'parent_id' => $parent?->id,
            'name' => $name,
            'slug' => $this->uniqueSiblingSlug($user, $parent?->id, $name),
            'depth' => $parent ? $parent->depth + 1 : 0,
            'sort_order' => $this->nextSortOrder($user, $parent?->id),
        ]);
    }

    /**
     * カテゴリを更新する。親が変わったら自分と全子孫の depth を再計算する。
     */
    public function update(Category $category, string $name, ?Category $parent): Category
    {
        $parentChanged = $category->parent_id !== $parent?->id;
        $nameChanged = $category->name !== $name;

        $category->name = $name;
        $category->parent_id = $parent?->id;
        $category->depth = $parent ? $parent->depth + 1 : 0;

        if ($parentChanged || $nameChanged) {
            $category->slug = $this->uniqueSiblingSlug(
                $category->user,
                $parent?->id,
                $name,
                ignoreId: $category->id,
            );
        }

        if ($parentChanged) {
            $category->sort_order = $this->nextSortOrder($category->user, $parent?->id);
        }

        $category->save();

        if ($parentChanged) {
            $this->recomputeDescendantDepths($category);
        }

        return $category;
    }

    /**
     * 指定カテゴリの全子孫の id（自分自身は含まない）。
     * 循環チェックや削除影響の把握に使う。深さ制限があるので最大2階層ぶん。
     *
     * @return list<int>
     */
    public function descendantIds(Category $category): array
    {
        $ids = [];
        $stack = $category->children()->pluck('id')->all();

        while ($stack !== []) {
            $id = array_pop($stack);
            $ids[] = $id;
            $stack = array_merge($stack, Category::where('parent_id', $id)->pluck('id')->all());
        }

        return $ids;
    }

    /**
     * このカテゴリを頂点とする部分木の高さ（葉なら 0、子がいれば 1、孫がいれば 2）。
     * 親を付け替えるとき「移動先の深さ + この高さ」が MAX_DEPTH に収まるか判定するのに使う。
     */
    public function subtreeHeight(Category $category): int
    {
        $children = $category->children;

        if ($children->isEmpty()) {
            return 0;
        }

        return 1 + $children->max(fn (Category $child) => $this->subtreeHeight($child));
    }

    /**
     * 親を付け替えたあと、子孫の depth を親からたどって振り直す。
     */
    private function recomputeDescendantDepths(Category $category): void
    {
        foreach ($category->children as $child) {
            $child->update(['depth' => $category->depth + 1]);
            $this->recomputeDescendantDepths($child);
        }
    }

    /**
     * 同じ親の兄弟の中で重複しない slug を作る。
     * 日本語名などで Str::slug() が空になる場合はランダムな接尾辞でしのぐ。
     */
    private function uniqueSiblingSlug(User $user, ?int $parentId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category-'.Str::lower(Str::random(6));

        $slug = $base;
        $suffix = 2;

        while ($this->siblingSlugExists($user, $parentId, $slug, $ignoreId)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function siblingSlugExists(User $user, ?int $parentId, string $slug, ?int $ignoreId): bool
    {
        return Category::query()
            ->where('user_id', $user->id)
            ->where('parent_id', $parentId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * 同じ親の末尾に並べるための sort_order。
     */
    private function nextSortOrder(User $user, ?int $parentId): int
    {
        $max = Category::query()
            ->where('user_id', $user->id)
            ->where('parent_id', $parentId)
            ->max('sort_order');

        return $max === null ? 0 : $max + 1;
    }
}
