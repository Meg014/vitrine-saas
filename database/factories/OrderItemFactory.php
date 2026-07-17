<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return ['order_id' => Order::factory(), 'product_name' => fake()->words(3, true), 'quantity' => 1, 'unit_price' => 1000, 'subtotal' => 1000];
    }
}
