<?php

namespace App\Support;

use App\Enums\StoreStatus;
use App\Models\Store;
use Illuminate\Session\Store as Session;

class PublicStoreContext
{
    private ?Store $store = null;

    public function __construct(private Session $session) {}

    public function set(Store $store): void
    {
        $this->store = $store;
        $this->session->put('public_store_id', $store->id);
    }

    public function get(): ?Store
    {
        if ($this->store) {
            return $this->store;
        }
        $id = $this->session->get('public_store_id');

        return $id ? $this->store = Store::with('settings')->where('status', StoreStatus::Active)->find($id) : null;
    }

    public function getOrFail(): Store
    {
        return $this->store ?? throw new \RuntimeException('Loja pública não resolvida.');
    }
}
