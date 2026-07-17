<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderAddressFactory extends Factory
{
    protected $model = OrderAddress::class;

    public function definition(): array
    {
        return ['order_id' => Order::factory(), 'recipient_name' => fake()->name(), 'postal_code' => '01001-000', 'street' => fake()->streetName(), 'number' => '100', 'neighborhood' => 'Centro', 'city' => 'São Paulo', 'state' => 'SP', 'country' => 'BR'];
    }
}
