<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Services\Category\CategoryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * カテゴリの作成・更新で共通するバリデーション。
 *
 * 単純なルール（必須・長さ・型）は rules() に、
 * 階層に関わる判定（親の所有者・深さ・循環・兄弟の名前重複）は after() にまとめる。
 * 具体的な認可（create か update か）はサブクラスの authorize() で行う。
 */
abstract class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // 存在チェック・所有者チェックは after() で行う（エラーメッセージを日本語で出したいため）。
            'parent_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * 追加の階層バリデーション。
     *
     * @return array<int, callable>
     */
    public function after(CategoryService $categories): array
    {
        return [
            function (Validator $validator) use ($categories) {
                $parent = $this->resolveParent();

                // parent_id が指定されているのに、自分のカテゴリとして見つからない。
                if ($this->filled('parent_id') && $parent === null) {
                    $validator->errors()->add('parent_id', '親カテゴリが見つかりません。');

                    return;
                }

                if ($parent !== null) {
                    $this->validateHierarchy($validator, $categories, $parent);
                }

                $this->validateSiblingNameUnique($validator, $parent?->id);
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'カテゴリ名',
            'parent_id' => '親カテゴリ',
        ];
    }

    /**
     * 更新対象のカテゴリ（更新リクエストのみ。作成時は null）。
     */
    protected function targetCategory(): ?Category
    {
        $category = $this->route('category');

        return $category instanceof Category ? $category : null;
    }

    /**
     * 入力された parent_id を、自分が所有するカテゴリとして解決する。
     * コントローラからも使うため public。
     */
    public function resolveParent(): ?Category
    {
        $parentId = $this->input('parent_id');

        if ($parentId === null || $parentId === '') {
            return null;
        }

        return Category::query()
            ->where('user_id', $this->user()->id)
            ->whereKey($parentId)
            ->first();
    }

    /**
     * 深さ制限と循環をチェックする。
     */
    private function validateHierarchy(Validator $validator, CategoryService $categories, Category $parent): void
    {
        $target = $this->targetCategory();

        // 循環: 親に自分自身、または自分の子孫を指定できない。
        if ($target !== null) {
            $forbidden = array_merge([$target->id], $categories->descendantIds($target));

            if (in_array($parent->id, $forbidden, true)) {
                $validator->errors()->add('parent_id', 'そのカテゴリは子孫にできません（循環します）。');

                return;
            }
        }

        // 深さ: 「親の深さ + 1 + 自分の部分木の高さ」が 3階層（MAX_DEPTH）に収まること。
        $subtreeHeight = $target !== null ? $categories->subtreeHeight($target) : 0;
        $resultingDepth = $parent->depth + 1 + $subtreeHeight;

        if ($resultingDepth > CategoryService::MAX_DEPTH) {
            $validator->errors()->add('parent_id', 'カテゴリは3階層までです。');
        }
    }

    /**
     * 同じ親の下に同名のカテゴリがないこと。
     */
    private function validateSiblingNameUnique(Validator $validator, ?int $parentId): void
    {
        $exists = Category::query()
            ->where('user_id', $this->user()->id)
            ->where('parent_id', $parentId)
            ->where('name', $this->input('name'))
            ->when($this->targetCategory() !== null, fn ($q) => $q->whereKeyNot($this->targetCategory()->id))
            ->exists();

        if ($exists) {
            $validator->errors()->add('name', '同じ場所に同じ名前のカテゴリがあります。');
        }
    }
}
