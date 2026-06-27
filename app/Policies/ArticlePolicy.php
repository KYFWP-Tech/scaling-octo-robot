<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    /**
     * Create a new policy instance.
     */
    public function index(User $user): bool
    {
        return $user->can('articles.index') || $user->profile_type === 'contributor';
    }

    public function store(User $user): bool
    {
        return $user->can('articles.store') || $user->profile_type === 'contributor';
    }

    public function show(User $user, Article $article): bool
    {
        return $user->can('articles.show') || $user->id == $article->user_id;
    }

    public function update(User $user, Article $article): bool
    {
        return $user->can('articles.update') || $user->id == $article->user_id;
    }

    public function destroy(User $user, Article $article): bool
    {
        return $user->can('articles.destroy') || $user->id == $article->user_id;
    }
}
