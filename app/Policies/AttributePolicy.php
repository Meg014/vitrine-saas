<?php

namespace App\Policies;

use App\Models\Attribute;
use App\Models\User;
use App\Support\CurrentStore;

class AttributePolicy
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

    public function update(User $user, Attribute $attribute): bool
    {
        return $attribute->store_id === $this->currentStore->get()?->id;
    }

    public function delete(User $user, Attribute $attribute): bool
    {
        return $this->update($user, $attribute) && ! $attribute->products()->exists();
    }
}
