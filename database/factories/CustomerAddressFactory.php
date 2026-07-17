<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return ['customer_id' => Customer::factory(), 'label' => 'Casa', 'recipient_name' => fake()->name(), 'postal_code' => fake()->numerify('########'), 'street' => fake()->streetName(), 'number' => fake()->buildingNumber(), 'neighborhood' => 'Centro', 'city' => fake()->city(), 'state' => 'SP', 'country' => 'BR', 'is_default' => false];
    }
}
