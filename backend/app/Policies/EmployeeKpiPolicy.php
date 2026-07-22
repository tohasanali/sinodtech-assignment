<?php

namespace App\Policies;

use App\Models\User;

class EmployeeKpiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
