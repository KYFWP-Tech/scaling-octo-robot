<?php

namespace App\Policies;

use App\Models\Podcast;
use App\Models\User;

class PodcastPolicy
{
    public function index(User $user): bool
    {
        return $user->can('podcasts.index') || $user->profile_type === 'contributor';
    }

    public function store(User $user): bool
    {
        return $user->can('podcasts.store') || $user->profile_type === 'contributor';
    }

    public function show(User $user, Podcast $podcast): bool
    {
        return $user->can('podcasts.show') || $user->id == $podcast->user_id;
    }

    public function update(User $user, Podcast $podcast): bool
    {
        return $user->can('podcasts.update') || $user->id == $podcast->user_id;
    }

    public function destroy(User $user, Podcast $podcast): bool
    {
        return ($user->can('podcasts.destroy') || $user->id == $podcast->user_id) && $podcast->episodes->count() == 0;
    }
}
