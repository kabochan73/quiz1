<?php

namespace App\Http\Requests;

/**
 * カテゴリ更新のリクエスト。
 * 親の付け替えに伴う循環チェック・深さチェックは CategoryRequest 側で対象カテゴリを見て行う。
 */
class UpdateCategoryRequest extends CategoryRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }
}
