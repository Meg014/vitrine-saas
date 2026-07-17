<?php

namespace App\Livewire\Orders;

use App\Enums\PaymentMethod;
use App\Support\CurrentStore;
use Livewire\Component;

class OrderSettings extends Component
{
    public bool $pickupEnabled = true;

    public bool $localDeliveryEnabled = false;

    public bool $shippingEnabled = false;

    public string $pickupInstructions = '';

    public string $localDeliveryInstructions = '';

    public string $shippingInstructions = '';

    public ?int $localDeliveryFee = 0;

    public ?int $defaultShippingFee = 0;

    public ?int $minimumOrderAmount = null;

    public array $paymentMethods = [];

    public function mount(CurrentStore $c): void
    {
        $s = $c->getOrFail()->settings()->firstOrCreate();
        foreach (['pickupEnabled' => 'pickup_enabled', 'localDeliveryEnabled' => 'local_delivery_enabled', 'shippingEnabled' => 'shipping_enabled', 'pickupInstructions' => 'pickup_instructions', 'localDeliveryInstructions' => 'local_delivery_instructions', 'shippingInstructions' => 'shipping_instructions', 'localDeliveryFee' => 'local_delivery_fee', 'defaultShippingFee' => 'default_shipping_fee', 'minimumOrderAmount' => 'minimum_order_amount', 'paymentMethods' => 'payment_methods'] as $p => $f) {
            $this->$p = $s->$f ?? ($p === 'paymentMethods' ? ['pix'] : null);
        }
    }

    public function save(CurrentStore $c): void
    {
        $this->validate(['localDeliveryFee' => 'nullable|integer|min:0', 'defaultShippingFee' => 'nullable|integer|min:0', 'minimumOrderAmount' => 'nullable|integer|min:0', 'paymentMethods' => 'required|array|min:1']);
        $c->getOrFail()->settings()->updateOrCreate([], ['pickup_enabled' => $this->pickupEnabled, 'local_delivery_enabled' => $this->localDeliveryEnabled, 'shipping_enabled' => $this->shippingEnabled, 'pickup_instructions' => $this->pickupInstructions ?: null, 'local_delivery_instructions' => $this->localDeliveryInstructions ?: null, 'shipping_instructions' => $this->shippingInstructions ?: null, 'local_delivery_fee' => $this->localDeliveryFee ?: 0, 'default_shipping_fee' => $this->defaultShippingFee ?: 0, 'minimum_order_amount' => $this->minimumOrderAmount, 'payment_methods' => $this->paymentMethods]);
        session()->flash('success', 'Configurações salvas.');
    }

    public function render()
    {
        return view('livewire.orders.order-settings', ['methods' => PaymentMethod::cases()]);
    }
}
