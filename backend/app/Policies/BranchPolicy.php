<?php

namespace App\Policies;

use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
