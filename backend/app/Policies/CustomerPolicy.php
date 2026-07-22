<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->isStaff();
    }

    public function assign(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }

    public function reengage(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }

    public function reengageAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
