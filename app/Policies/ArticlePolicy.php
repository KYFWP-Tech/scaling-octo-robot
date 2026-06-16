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
        return $user->can('articles.index');
    }

    public function store(User $user): bool
    {
        return $user->can('articles.store');
    }

    public function show(User $user, Article $article): bool
    {
        return $user->can('articles.show');
    }

    public function update(User $user, Article $article): bool
    {
        return $user->can('articles.update') || $user->id == $article->author_id;
    }

    public function destroy(User $user, Article $article): bool
    {
        return $user->can('articles.destroy') || $user->id == $article->author_id;
    }
}
