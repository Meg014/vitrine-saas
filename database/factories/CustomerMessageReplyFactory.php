<?php

namespace Database\Factories;

use App\Models\CustomerMessage;
use App\Models\CustomerMessageReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerMessageReplyFactory extends Factory
{
    protected $model = CustomerMessageReply::class;

    public function definition(): array
    {
        return ['customer_message_id' => CustomerMessage::factory(), 'user_id' => User::factory(), 'message' => fake()->paragraph(), 'is_internal' => false];
    }
}
