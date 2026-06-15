<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Permission;

class PermissionPolicy
{
    public function index(User $user): bool
    {
        return $user->can('permissions.index');
    }

    public function show(User $user, Permission $permission): bool
    {
        return $user->can('permissions.show');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can('permissions.update');
    }
}
