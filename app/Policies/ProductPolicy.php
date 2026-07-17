<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\CurrentStore;

class ProductPolicy
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

    public function view(User $user, Product $product): bool
    {
        return $product->store_id === $this->currentStore->get()?->id;
    }

    public function update(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }
}
