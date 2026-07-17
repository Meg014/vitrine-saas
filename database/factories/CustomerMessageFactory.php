<?php

namespace Database\Factories;

use App\Enums\CustomerMessageSource;
use App\Enums\CustomerMessageStatus;
use App\Models\CustomerMessage;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerMessageFactory extends Factory
{
    protected $model = CustomerMessage::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'name' => fake()->name(), 'email' => fake()->safeEmail(), 'subject' => fake()->sentence(4), 'message' => fake()->paragraph(), 'status' => CustomerMessageStatus::New, 'source' => CustomerMessageSource::ContactForm];
    }
}
