<?php

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    /** 自分自身は停止できません（全管理者のロックアウト防止）。 */
    public function deactivate(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id;
    }
}
