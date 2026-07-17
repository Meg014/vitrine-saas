<?php

namespace App\Actions;

use App\Enums\CustomerMessageStatus;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Store;
use Illuminate\Validation\ValidationException;

class SaveCustomerMessage
{
    public function handle(Store $store, array $data, ?CustomerMessage $message = null): CustomerMessage
    {
        if (($data['customer_id'] ?? null) && ! Customer::whereKey($data['customer_id'])->where('store_id', $store->id)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'O cliente não pertence à loja atual.']);
        }if (($data['assigned_user_id'] ?? null) && ! $store->users()->whereKey($data['assigned_user_id'])->exists()) {
            throw ValidationException::withMessages(['assigned_user_id' => 'O responsável não pertence à loja atual.']);
        }$data['email'] = filled($data['email'] ?? null) ? strtolower(trim($data['email'])) : null;
        $data['phone'] = filled($data['phone'] ?? null) ? preg_replace('/\D+/', '', $data['phone']) : null;
        $message ??= new CustomerMessage(['status' => CustomerMessageStatus::New]);
        $message->fill($data);
        $store->customerMessages()->save($message);

        return $message;
    }
}
