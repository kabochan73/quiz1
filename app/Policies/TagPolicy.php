<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

/**
 * タグの認可。所有者だけが編集・削除できる。
 *
 * タグはクイズ編集画面から「名前を打つと作られる」運用なので、
 * 単独の一覧・作成画面は v1 では作らない（view / create もひとまず所有者ベースで定義しておく）。
 */
class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tag $tag): bool
    {
        return $this->owns($user, $tag);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tag $tag): bool
    {
        return $this->owns($user, $tag);
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $this->owns($user, $tag);
    }

    private function owns(User $user, Tag $tag): bool
    {
        return $tag->user_id === $user->id;
    }
}
