<?php

namespace App\Policies;

use App\Models\InsurancePlan;
use App\Models\User;

final class InsurancePlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, InsurancePlan $plan): bool
    {
        return $user->isAdmin() && $plan->status !== InsurancePlan::STATUS_DELETED;
    }

    public function changePrice(User $user, InsurancePlan $plan): bool
    {
        return $this->update($user, $plan);
    }

    public function delete(User $user, InsurancePlan $plan): bool
    {
        return $user->isAdmin();
    }

    /** 契約時金額の上書きは管理者のみ（仕様 7.6）。 */
    public function overrideContractPrice(User $user): bool
    {
        return $user->isAdmin();
    }
}
