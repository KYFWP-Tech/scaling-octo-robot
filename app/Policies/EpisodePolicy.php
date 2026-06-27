<?php

namespace App\Policies;

use App\Models\Episode;
use App\Models\Podcast;
use App\Models\User;

class EpisodePolicy
{
    public function index(User $user): bool
    {
        return $user->can('episodes.index') || $user->profile_type === 'contributor';
    }

    public function show(User $user, Episode $episode): bool
    {
        return $user->can('episodes.show') || $episode->podcast->user_id == $user->id;
    }

    public function store(User $user, Podcast $podcast): bool
    {
        return ($user->can('episodes.store') || $user->profile_type === 'contributor') && $podcast->user_id == $user->id;
    }

    public function update(User $user, Episode $episode): bool
    {
        return $user->can('episodes.update') || $episode->podcast->user_id == $user->id;
    }

    public function destroy(User $user, Episode $episode): bool
    {
        return $user->can('episodes.destroy') || $episode->podcast->user_id == $user->id;
    }
}
