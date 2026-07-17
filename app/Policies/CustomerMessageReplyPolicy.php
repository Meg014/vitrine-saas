<?php

namespace App\Policies;

use App\Models\CustomerMessageReply;
use App\Models\User;
use App\Support\CurrentStore;

class CustomerMessageReplyPolicy
{
    public function __construct(private CurrentStore $current) {}

    public function view(User $u, CustomerMessageReply $r): bool
    {
        return $r->customerMessage()->where('store_id', $this->current->get()?->id)->exists();
    }

    public function update(User $u, CustomerMessageReply $r): bool
    {
        return false;
    }

    public function delete(User $u, CustomerMessageReply $r): bool
    {
        return false;
    }
}
