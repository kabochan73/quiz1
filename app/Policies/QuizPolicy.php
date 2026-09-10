<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

/**
 * クイズの認可。所有者だけが閲覧・編集・削除できる。
 * v1 は「公開クイズを他人が受験する」機能がないので、view も所有者限定でよい。
 *
 * 命名規約（Quiz → QuizPolicy）で自動的に紐付く。
 */
class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    private function owns(User $user, Quiz $quiz): bool
    {
        return $quiz->user_id === $user->id;
    }
}
