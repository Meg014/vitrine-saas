<?php

namespace App\Livewire\Storefront;

use App\Actions\CreateOrder;
use App\Actions\ResolveCart;
use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Support\PublicStoreContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class Checkout extends Component
{
    public int $step = 1;

    public ?int $addressId = null;

    public string $shippingMethod = 'pickup';

    public string $paymentMethod = 'pix';

    public string $customerNotes = '';

    public bool $terms = false;

    public string $checkoutToken = '';

    public function mount(PublicStoreContext $c): void
    {
        $this->checkoutToken = (string) Str::uuid();
        $customer = Auth::guard('customer')->user();
        $this->addressId = $customer->addresses()->where('is_default', true)->value('id') ?? $customer->addresses()->value('id');
        $s = $c->getOrFail()->settings()->firstOrCreate();
        $this->shippingMethod = $s->pickup_enabled ? 'pickup' : ($s->local_delivery_enabled ? 'local_delivery' : 'shipping');
        $this->paymentMethod = ($s->payment_methods ?: ['pix'])[0];
    }

    public function next(): void
    {
        $this->validateCurrent();
        $this->step = min(3, $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    private function validateCurrent(): void
    {
        if ($this->step === 1 && $this->shippingMethod !== 'pickup') {
            $this->validate(['addressId' => 'required|integer']);
        }if ($this->step === 2) {
            $this->validate(['paymentMethod' => 'required']);
        }
    }

    public function placeOrder(CreateOrder $create, ResolveCart $resolve, PublicStoreContext $c)
    {
        $this->validate(['terms' => 'accepted']);
        $store = $c->getOrFail();
        $customer = Auth::guard('customer')->user();
        $order = $create->handle($store, $customer, $resolve->handle($store, $customer), ['address_id' => $this->addressId, 'shipping_method' => $this->shippingMethod, 'payment_method' => $this->paymentMethod, 'customer_notes' => $this->customerNotes], $this->checkoutToken);

        return $this->redirectRoute('store.order.success', ['store' => $store, 'order' => $order], navigate: true);
    }

    public function render(ResolveCart $resolve, PublicStoreContext $c)
    {
        $store = $c->getOrFail();
        $customer = Auth::guard('customer')->user();
        $cart = $resolve->handle($store, $customer)->load('items');
        $settings = $store->settings()->firstOrCreate();
        $addresses = $customer->addresses()->get();
        $shippingOptions = collect(ShippingMethod::cases())->filter(fn ($m) => match ($m) {
            ShippingMethod::Pickup => $settings->pickup_enabled,ShippingMethod::LocalDelivery => $settings->local_delivery_enabled,ShippingMethod::Shipping => $settings->shipping_enabled
        });
        $payments = collect(PaymentMethod::cases())->filter(fn ($m) => in_array($m->value, $settings->payment_methods ?: array_column(PaymentMethod::cases(), 'value'), true));
        $shippingFee = match (ShippingMethod::tryFrom($this->shippingMethod)) {
            ShippingMethod::LocalDelivery => (int) $settings->local_delivery_fee,ShippingMethod::Shipping => (int) $settings->default_shipping_fee,default => 0
        };

        return view('livewire.storefront.checkout', compact('cart', 'settings', 'addresses', 'shippingOptions', 'payments', 'shippingFee'));
    }
}
