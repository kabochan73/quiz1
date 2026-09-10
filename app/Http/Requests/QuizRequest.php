<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * クイズの作成・更新で共通するバリデーション。
 *
 * クイズ自体は階層を持たないので、カテゴリほど複雑な判定は要らない。
 * - category_id は「自分のカテゴリ」であること（どの階層でも可）
 * - tags は自由入力のカンマ区切り文字列。名前 → Tag への解決は TagService が行う
 * - 公開（is_published）はここでは扱わない。専用アクション（publish/unpublish）で
 *   「問題が1〜30問あるか」を見て切り替える
 */
abstract class QuizRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => [
                'nullable',
                'integer',
                // 存在し、かつ自分のカテゴリであること。
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
            // "頻出, 苦手" のようなカンマ区切り。空でも可。
            'tags' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'description' => '説明',
            'category_id' => 'カテゴリ',
            'tags' => 'タグ',
        ];
    }

    /**
     * 入力された tags 文字列を、トリム済みのタグ名リストに分解する。
     *
     * @return array<int, string>
     */
    public function tagNames(): array
    {
        $raw = $this->string('tags')->toString();

        return collect(explode(',', $raw))
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '')
            ->unique()
            ->values()
            ->all();
    }
}
