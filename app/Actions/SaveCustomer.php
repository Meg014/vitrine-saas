<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Support\Str;

class SaveCustomer
{
    public function handle(Store $store, array $data, ?Customer $customer = null): Customer
    {
        $normalized = [...$data, 'email' => filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null, 'phone' => $this->digits($data['phone'] ?? null), 'document' => $this->digits($data['document'] ?? null)];
        if ($customer) {
            $customer->update($normalized);

            return $customer;
        }$created = new Customer($normalized);
        $store->customers()->save($created);

        return $created;
    }

    private function digits(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value ?? '');

        return $digits !== '' ? $digits : null;
    }
}
