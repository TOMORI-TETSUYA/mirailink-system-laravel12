<?php

namespace App\Policies;

use App\Models\User;

final class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAuditor();
    }
}
