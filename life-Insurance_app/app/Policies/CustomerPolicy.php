<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

final class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->isStaff()) {
            return $customer->assigned_user_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStaff() && $customer->assigned_user_id === $user->id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }

    /** 要配慮個人情報（健康情報）の閲覧。管理者と担当職員のみ。 */
    public function viewHealth(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStaff() && $customer->assigned_user_id === $user->id;
    }

    public function export(User $user): bool
    {
        return $user->isAdmin();
    }
}
