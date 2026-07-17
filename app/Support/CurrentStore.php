<?php

namespace App\Support;

use App\Models\Store;
use Illuminate\Session\Store as Session;

class CurrentStore
{
    private ?Store $resolved = null;

    public function __construct(private readonly Session $session) {}

    public function get(): ?Store
    {
        if ($this->resolved) {
            return $this->resolved;
        }

        $id = $this->session->get('current_store_id');

        return $id ? $this->resolved = Store::with('settings')->find($id) : null;
    }

    public function getOrFail(): Store
    {
        return $this->get() ?? throw new \RuntimeException('Nenhuma loja atual foi selecionada.');
    }

    public function set(Store $store): void
    {
        $this->session->put('current_store_id', $store->getKey());
        $this->resolved = $store;
    }

    public function forget(): void
    {
        $this->session->forget('current_store_id');
        $this->resolved = null;
    }
}
