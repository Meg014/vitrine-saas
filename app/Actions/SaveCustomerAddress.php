<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;

class SaveCustomerAddress
{
    public function handle(Customer $customer, array $data, ?CustomerAddress $address = null): CustomerAddress
    {
        return DB::transaction(function () use ($customer, $data, $address) {
            $data['postal_code'] = preg_replace('/\D+/', '', $data['postal_code']);
            $data['phone'] = filled($data['phone'] ?? null) ? preg_replace('/\D+/', '', $data['phone']) : null;
            $data['state'] = strtoupper($data['state']);
            $data['country'] = strtoupper($data['country'] ?? 'BR');
            if ($data['is_default'] ?? false) {
                $customer->addresses()->whereKeyNot($address?->id)->update(['is_default' => false]);
            }$address ??= new CustomerAddress;
            $address->fill($data);
            $customer->addresses()->save($address);
            if (! $customer->addresses()->where('is_default', true)->exists()) {
                $address->update(['is_default' => true]);
            }

            return $address;
        });
    }
}
