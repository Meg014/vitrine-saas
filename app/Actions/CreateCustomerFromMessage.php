<?php

namespace App\Actions;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCustomerFromMessage
{
    public function __construct(private SaveCustomer $saveCustomer) {}

    public function handle(CustomerMessage $message, User $user): Customer
    {
        if (! $message->store->users()->whereKey($user->id)->exists()) {
            throw ValidationException::withMessages(['message' => 'A mensagem não pertence à loja atual.']);
        }

        return DB::transaction(function () use ($message) {
            $customer = $message->email
                ? Customer::where('store_id', $message->store_id)->where('email', strtolower($message->email))->first()
                : null;
            if (! $customer) {
                $customer = $this->saveCustomer->handle($message->store, ['name' => $message->name, 'email' => $message->email, 'phone' => $message->phone, 'status' => CustomerStatus::Active, 'accepts_marketing' => false]);
            }$message->update(['customer_id' => $customer->id]);

            return $customer;
        });
    }
}
