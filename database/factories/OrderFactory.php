<?php

namespace Database\Factories;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $seq = fake()->unique()->numberBetween(1, 999999);

        return ['store_id' => Store::factory(), 'customer_id' => Customer::factory(), 'sequence_number' => $seq, 'number' => 'PED-'.str_pad($seq, 6, '0', STR_PAD_LEFT), 'checkout_token' => (string) Str::uuid(), 'status' => OrderStatus::Pending, 'payment_status' => PaymentStatus::Pending, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'payment_method' => PaymentMethod::Pix, 'shipping_method' => ShippingMethod::Pickup, 'subtotal' => 1000, 'shipping_amount' => 0, 'discount_amount' => 0, 'total' => 1000, 'currency' => 'BRL', 'customer_name' => fake()->name(), 'customer_email' => fake()->email()];
    }
}
