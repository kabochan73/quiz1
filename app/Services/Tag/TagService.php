<?php

namespace App\Services\Tag;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * タグの解決サービス。
 *
 * タグは独立した管理画面を持たず、クイズ編集画面で「名前を打つと作られる」運用。
 * そのため「タグ名の配列 → そのユーザーの Tag id の配列（無ければ作成）」という
 * 変換をここに集約する。
 */
class TagService
{
    /**
     * タグ名の配列を、指定ユーザーの Tag の id 配列へ解決する。
     * 存在しない名前は新しく作る。
     *
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    public function resolveIds(User $user, array $names): array
    {
        $ids = [];

        foreach ($this->normalize($names) as $name) {
            $ids[] = $this->firstOrCreate($user, $name)->id;
        }

        return $ids;
    }

    /**
     * 前後の空白を除去し、空文字を捨て、重複（完全一致）をまとめる。
     *
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    private function normalize(array $names): array
    {
        return collect($names)
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function firstOrCreate(User $user, string $name): Tag
    {
        $existing = Tag::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Tag::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $this->uniqueSlug($user, $name),
        ]);
    }

    /**
     * ユーザー内で重複しない slug を作る。日本語名などは tag-xxxxxx にフォールバック。
     */
    private function uniqueSlug(User $user, string $name): string
    {
        $base = Str::slug($name) ?: 'tag-'.Str::lower(Str::random(6));

        $slug = $base;
        $suffix = 2;

        while (Tag::where('user_id', $user->id)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
