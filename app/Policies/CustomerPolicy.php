<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Support\CurrentStore;

class CustomerPolicy
{
    public function __construct(private CurrentStore $current) {}

    public function viewAny(User $u): bool
    {
        return $this->current->get() !== null;
    }

    public function create(User $u): bool
    {
        return $this->viewAny($u);
    }

    public function view(User $u, Customer $c): bool
    {
        return $c->store_id === $this->current->get()?->id;
    }

    public function update(User $u, Customer $c): bool
    {
        return $this->view($u, $c);
    }

    public function delete(User $u, Customer $c): bool
    {
        return $this->view($u, $c) && ! $c->messages()->exists() && ! $c->addresses()->exists();
    }
}
