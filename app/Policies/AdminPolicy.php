<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class AdminPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function index(User $user): bool
    {
        return $user->profile instanceof Admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function show(User $user, Admin $admin): bool
    {
        return $user->profile instanceof Admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function store(User $user): bool
    {
        return $user->profile instanceof Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Admin $admin): bool
    {
        return $user->profile instanceof Admin && $user->profile->id !== $admin->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function destroy(User $user, Admin $admin): bool
    {
        return $user->profile instanceof Admin && $user->profile->id !== $admin->id;
    }
}
