<?php

namespace App\Actions;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Session\Store as Session;

class ResolveCart
{
    public function __construct(private Session $session) {}

    public function handle(Store $store, ?Customer $customer = null): Cart
    {
        $query = Cart::where('store_id', $store->id)->where('status', CartStatus::Active);
        if ($customer) {
            $cart = (clone $query)->where('customer_id', $customer->id)->first();
        } else {
            $sessionId = $this->session->getId();
            $cart = (clone $query)->whereNull('customer_id')->where('session_id', $sessionId)->first();
        }if ($cart) {
            return $cart;
        }

        return $store->carts()->create(['customer_id' => $customer?->id, 'session_id' => $customer ? null : $this->session->getId(), 'status' => CartStatus::Active, 'expires_at' => now()->addDays(30)]);
    }
}
