<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
  public function index(User $user): bool
  {
    return $user->can('categories.index');
  }

  public function store(User $user): bool
  {
    return $user->can('categories.store');
  }

  public function show(User $user, Category $category): bool
  {
    return $user->can('categories.show');
  }

  public function update(User $user, Category $category): bool
  {
    return $user->can('categories.update');
  }

  public function destroy(User $user, Category $category): bool
  {
    return $user->can('categories.destroy');
  }
}
