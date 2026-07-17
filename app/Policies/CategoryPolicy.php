<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Support\CurrentStore;

class CategoryPolicy
{
    public function __construct(private CurrentStore $currentStore) {}

    public function viewAny(User $user): bool
    {
        return $this->currentStore->get() !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $category->store_id === $this->currentStore->get()?->id;
    }

    public function update(User $user, Category $category): bool
    {
        return $this->view($user, $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->view($user, $category) && ! $category->products()->exists() && ! $category->children()->exists();
    }
}
