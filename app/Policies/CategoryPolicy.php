<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * カテゴリの認可。
 *
 * このアプリは単一ユーザー運用だが、すべてのリソースを「所有者だけが操作できる」で統一する。
 * Laravel 11+ は命名規約（Category → CategoryPolicy）でこのクラスを自動的に紐付けるため、
 * AuthServiceProvider への登録は不要。
 */
class CategoryPolicy
{
    /** 一覧は、ログインしていれば自分のぶんを見られる（絞り込みはクエリ側で行う）。 */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return $this->owns($user, $category);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Category $category): bool
    {
        return $this->owns($user, $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->owns($user, $category);
    }

    /** 所有者かどうか。 */
    private function owns(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }
}
