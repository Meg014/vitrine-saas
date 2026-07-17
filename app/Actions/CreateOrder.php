<?php

namespace App\Actions;

use App\Enums\CartStatus;
use App\Enums\CustomerStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ShippingMethod;
use App\Enums\StoreStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    public function handle(Store $store, Customer $customer, Cart $cart, array $data, string $token): Order
    {
        if ($existing = Order::whereBelongsTo($store)->where('checkout_token', $token)->first()) {
            return $existing->load(['items', 'address']);
        }
        if ($customer->store_id !== $store->id || $customer->status !== CustomerStatus::Active) {
            throw ValidationException::withMessages(['customer' => 'Cliente inválido ou inativo.']);
        }
        if ($cart->store_id !== $store->id || $cart->customer_id !== $customer->id || $cart->status !== CartStatus::Active) {
            throw ValidationException::withMessages(['cart' => 'Carrinho inválido.']);
        }

        return DB::transaction(function () use ($store, $customer, $cart, $data, $token): Order {
            if ($existing = Order::where('store_id', $store->id)->where('checkout_token', $token)->lockForUpdate()->first()) {
                return $existing->load(['items', 'address']);
            }
            $lockedStore = Store::lockForUpdate()->findOrFail($store->id);
            if ($lockedStore->status !== StoreStatus::Active) {
                throw ValidationException::withMessages(['store' => 'A loja não está disponível.']);
            }
            $lockedCart = Cart::lockForUpdate()->findOrFail($cart->id);
            if ($lockedCart->status !== CartStatus::Active) {
                throw ValidationException::withMessages(['cart' => 'Este carrinho já foi finalizado.']);
            }
            $settings = $lockedStore->settings()->firstOrCreate();
            $shipping = ShippingMethod::tryFrom($data['shipping_method'] ?? '');
            $payment = PaymentMethod::tryFrom($data['payment_method'] ?? '');
            if (! $shipping || ! $payment) {
                throw ValidationException::withMessages(['checkout' => 'Selecione entrega e pagamento válidos.']);
            }
            $enabledShipping = match ($shipping) {
                ShippingMethod::Pickup => $settings->pickup_enabled,ShippingMethod::LocalDelivery => $settings->local_delivery_enabled,ShippingMethod::Shipping => $settings->shipping_enabled
            };
            if (! $enabledShipping) {
                throw ValidationException::withMessages(['shipping_method' => 'Forma de entrega indisponível.']);
            }
            $enabledPayments = $settings->payment_methods ?: [PaymentMethod::Pix->value, PaymentMethod::BankTransfer->value, PaymentMethod::PayOnPickup->value, PaymentMethod::CashOnDelivery->value];
            if (! in_array($payment->value, $enabledPayments, true)) {
                throw ValidationException::withMessages(['payment_method' => 'Forma de pagamento indisponível.']);
            }
            if ($payment === PaymentMethod::PayOnPickup && $shipping !== ShippingMethod::Pickup) {
                throw ValidationException::withMessages(['payment_method' => 'Pagamento na retirada exige retirada na loja.']);
            }
            if ($payment === PaymentMethod::CashOnDelivery && $shipping !== ShippingMethod::LocalDelivery) {
                throw ValidationException::withMessages(['payment_method' => 'Dinheiro na entrega exige entrega local.']);
            }
            $address = null;
            if ($shipping !== ShippingMethod::Pickup) {
                $address = CustomerAddress::whereBelongsTo($customer)->find($data['address_id'] ?? 0);
                if (! $address) {
                    throw ValidationException::withMessages(['address_id' => 'Selecione um endereço válido.']);
                }
            }
            $cartItems = $lockedCart->items()->get();
            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Seu carrinho está vazio.']);
            }
            $prepared = [];
            $subtotal = 0;
            foreach ($cartItems as $cartItem) {
                $product = Product::where('store_id', $store->id)->lockForUpdate()->find($cartItem->product_id);
                if (! $product || $product->status !== ProductStatus::Active || ! $product->published_at || $product->published_at->isFuture()) {
                    throw ValidationException::withMessages(['cart' => 'Um produto não está mais disponível.']);
                }
                $variant = null;
                if ($cartItem->product_variant_id) {
                    $variant = ProductVariant::where('product_id', $product->id)->lockForUpdate()->find($cartItem->product_variant_id);
                    if (! $variant || ! $variant->is_active) {
                        throw ValidationException::withMessages(['cart' => 'Uma variação não está mais disponível.']);
                    }
                }
                $unit = (int) ($variant ? ($variant->promotional_price ?? $variant->price) : ($product->promotional_price ?? $product->base_price));
                $stock = (int) ($variant?->stock_quantity ?? $product->stock_quantity);
                if ($product->track_inventory && $stock < $cartItem->quantity) {
                    throw ValidationException::withMessages(['cart' => "Estoque insuficiente para {$product->name}."]);
                }
                $line = $unit * $cartItem->quantity;
                $subtotal += $line;
                $attributes = $variant?->attributeValues()->with('attribute')->get()->mapWithKeys(fn ($v) => [$v->attribute->name => $v->name])->all();
                $prepared[] = compact('cartItem', 'product', 'variant', 'unit', 'line', 'stock', 'attributes');
            }
            if ($settings->minimum_order_amount && $subtotal < $settings->minimum_order_amount) {
                throw ValidationException::withMessages(['cart' => 'O pedido não atingiu o valor mínimo da loja.']);
            }
            $shippingAmount = match ($shipping) {
                ShippingMethod::Pickup => 0,ShippingMethod::LocalDelivery => (int) $settings->local_delivery_fee,ShippingMethod::Shipping => (int) $settings->default_shipping_fee
            };
            $sequence = (int) $lockedStore->next_order_number;
            $lockedStore->update(['next_order_number' => $sequence + 1]);
            $order = $lockedStore->orders()->create(['customer_id' => $customer->id, 'cart_id' => $cart->id, 'sequence_number' => $sequence, 'number' => 'PED-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT), 'checkout_token' => $token, 'status' => OrderStatus::Pending, 'payment_status' => PaymentStatus::Pending, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'payment_method' => $payment, 'shipping_method' => $shipping, 'subtotal' => $subtotal, 'shipping_amount' => $shippingAmount, 'discount_amount' => 0, 'total' => $subtotal + $shippingAmount, 'currency' => $settings->currency ?: 'BRL', 'customer_notes' => $data['customer_notes'] ?? null, 'customer_name' => $customer->name, 'customer_email' => $customer->email, 'customer_phone' => $customer->phone, 'customer_document' => $customer->document]);
            foreach ($prepared as $p) {
                $order->items()->create(['product_id' => $p['product']->id, 'product_variant_id' => $p['variant']?->id, 'product_name' => $p['product']->name, 'variant_name' => $p['variant']?->name, 'sku' => $p['variant']?->sku ?? $p['product']->sku, 'attributes' => $p['attributes'], 'quantity' => $p['cartItem']->quantity, 'unit_price' => $p['unit'], 'subtotal' => $p['line']]);
                if ($p['product']->track_inventory) {
                    $target = $p['variant'] ?: $p['product'];
                    $new = $p['stock'] - $p['cartItem']->quantity;
                    $target->update(['stock_quantity' => $new]);
                    InventoryMovement::create(['store_id' => $store->id, 'product_id' => $p['product']->id, 'product_variant_id' => $p['variant']?->id, 'type' => InventoryMovementType::Exit, 'quantity' => -$p['cartItem']->quantity, 'previous_quantity' => $p['stock'], 'new_quantity' => $new, 'reason' => 'Venda '.$order->number, 'reference_type' => Order::class, 'reference_id' => $order->id]);
                }
            }
            if ($address) {
                $order->address()->create(['recipient_name' => $address->recipient_name, 'postal_code' => $address->postal_code, 'street' => $address->street, 'number' => $address->number, 'complement' => $address->complement, 'neighborhood' => $address->neighborhood, 'city' => $address->city, 'state' => $address->state, 'country' => $address->country, 'phone' => $address->phone]);
            }
            $order->histories()->create(['customer_id' => $customer->id, 'to_status' => OrderStatus::Pending->value, 'comment' => 'Pedido criado pelo checkout.']);
            $lockedCart->update(['status' => CartStatus::Converted]);
            $customer->update(['last_purchase_at' => now()]);

            return $order->load(['items', 'address']);
        }, 3);
    }
}
