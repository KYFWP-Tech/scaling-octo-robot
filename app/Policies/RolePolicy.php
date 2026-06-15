<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function index(User $user): bool
    {
        return $user->can('roles.index');
    }

    public function show(User $user, Role $role): bool
    {
        return $user->can('roles.show');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.update');
    }

    public function destroy(User $user, Role $role): bool
    {
        return $user->can('roles.destroy');
    }
}
