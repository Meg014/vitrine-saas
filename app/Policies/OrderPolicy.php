<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Support\CurrentStore;

class OrderPolicy
{
    public function __construct(private CurrentStore $current) {}

    public function viewAny(User $user): bool
    {
        return (bool) $this->current->get();
    }

    public function view(User $user, Order $order): bool
    {
        return $order->store_id === $this->current->get()?->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }
}
