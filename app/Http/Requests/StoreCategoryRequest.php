<?php

namespace App\Http\Requests;

use App\Models\Category;

/**
 * カテゴリ新規作成のリクエスト。ルールは CategoryRequest と共通。
 */
class StoreCategoryRequest extends CategoryRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
    }
}
