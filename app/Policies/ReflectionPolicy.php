<?php

namespace App\Policies;

use App\Models\Reflection;
use App\Models\User;

class ReflectionPolicy
{
    public function index(User $user): bool
    {
        return $user->can('reflections.index') || $user->profile_type === 'contributor';
    }

    public function store(User $user): bool
    {
        return $user->can('reflections.store') || $user->profile_type === 'contributor';
    }

    public function show(User $user, Reflection $reflection): bool
    {
        return $user->can('reflections.show') || $user->id == $reflection->author_id;
    }

    public function update(User $user, Reflection $reflection): bool
    {
        return $user->can('reflections.update') || $user->id == $reflection->author_id;
    }

    public function destroy(User $user, Reflection $reflection): bool
    {
        return $user->can('reflections.destroy') || $user->id == $reflection->author_id;
    }
}
