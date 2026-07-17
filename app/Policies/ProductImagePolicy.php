<?php

namespace App\Policies;

use App\Models\ProductImage;
use App\Models\User;
use App\Support\CurrentStore;

class ProductImagePolicy
{
    public function __construct(private CurrentStore $currentStore) {}

    public function delete(User $user, ProductImage $image): bool
    {
        return $image->product()->where('store_id', $this->currentStore->get()?->id)->exists();
    }
}
