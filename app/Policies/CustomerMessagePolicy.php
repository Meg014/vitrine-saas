<?php

namespace App\Policies;

use App\Models\CustomerMessage;
use App\Models\User;
use App\Support\CurrentStore;

class CustomerMessagePolicy
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

    public function view(User $u, CustomerMessage $m): bool
    {
        return $m->store_id === $this->current->get()?->id;
    }

    public function update(User $u, CustomerMessage $m): bool
    {
        return $this->view($u, $m);
    }

    public function delete(User $u, CustomerMessage $m): bool
    {
        return false;
    }
}
