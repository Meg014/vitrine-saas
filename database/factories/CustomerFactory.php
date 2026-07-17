<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'name' => fake()->name(), 'email' => fake()->unique()->safeEmail(), 'phone' => fake()->numerify('119########'), 'document' => fake()->unique()->numerify('###########'), 'birth_date' => fake()->dateTimeBetween('-70 years', '-18 years'), 'status' => CustomerStatus::Active, 'accepts_marketing' => false];
    }
}
