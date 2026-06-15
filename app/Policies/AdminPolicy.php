<?php

namespace App\Policies;

use App\Enums\Role as AdminRole;
use App\Models\Admin;
use App\Models\User;

class AdminPolicy
{
    public function index(User $user): bool
    {
        return $user->can('admins.index');
    }

    public function show(User $user, Admin $admin): bool
    {
        return $user->can('admins.show');
    }

    public function store(User $user): bool
    {
        return $user->can('admins.store');
    }

    public function update(User $user, Admin $admin): bool
    {
        return $user->can('admins.update') && $user->profile?->id !== $admin->id;
    }

    public function destroy(User $user, Admin $admin): bool
    {
        if (! $user->can('admins.destroy') || $user->profile?->id === $admin->id) {
            return false;
        }

        if (! $admin->user->hasRole(AdminRole::SuperAdmin->value)) {
            return true;
        }

        return User::role(AdminRole::SuperAdmin->value)
            ->whereKeyNot($admin->user->id)
            ->exists();
    }

    public function assignRole(User $user, Admin $admin): bool
    {

        if ($admin->user->hasRole(AdminRole::SuperAdmin->value)) {
            return User::role(AdminRole::SuperAdmin->value)
                ->whereKeyNot($admin->user->id)
                ->exists() && $user->can('roles.update');
        }

        return $user->can('roles.update');
    }
}
