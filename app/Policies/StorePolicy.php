<?php

namespace App\Policies;

use App\Enums\StoreRole;
use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function view(User $user, Store $store): bool
    {
        return $user->stores()->whereKey($store->getKey())->exists();
    }

    public function update(User $user, Store $store): bool
    {
        return $user->stores()
            ->whereKey($store->getKey())
            ->wherePivotIn('role', [StoreRole::Owner->value, StoreRole::Admin->value])
            ->exists();
    }

    public function manageTeam(User $user, Store $store): bool
    {
        return $this->update($user, $store);
    }
}
