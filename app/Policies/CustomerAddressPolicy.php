<?php

namespace App\Policies;

use App\Models\CustomerAddress;
use App\Models\User;
use App\Support\CurrentStore;

class CustomerAddressPolicy
{
    public function __construct(private CurrentStore $current) {}

    public function update(User $u, CustomerAddress $a): bool
    {
        return $a->customer()->where('store_id', $this->current->get()?->id)->exists();
    }

    public function delete(User $u, CustomerAddress $a): bool
    {
        return $this->update($u, $a);
    }
}
