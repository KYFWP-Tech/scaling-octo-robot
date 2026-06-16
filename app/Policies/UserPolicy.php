<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{

    public function index(User $user): bool
    {
        return $user->can('users.index');
    }

    public function show(User $user, User $userToShow): bool
    {
        return $user->can('users.show');
    }

    public function update(User $user, User $userModify): bool
    {
        return $user->can('users.update');
    }

    public function destroy(User $user, User $userToDelete): bool
    {
        return $user->can('users.update') && $user->id != $userToDelete->id;
    }

}
