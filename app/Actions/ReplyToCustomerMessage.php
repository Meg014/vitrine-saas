<?php

namespace App\Actions;

use App\Enums\CustomerMessageStatus;
use App\Models\CustomerMessage;
use App\Models\CustomerMessageReply;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplyToCustomerMessage
{
    public function handle(CustomerMessage $message, User $user, string $body, bool $internal = false): CustomerMessageReply
    {
        if (! $message->store->users()->whereKey($user->id)->exists()) {
            throw ValidationException::withMessages(['user' => 'O usuário não pertence à loja da mensagem.']);
        }

        return DB::transaction(function () use ($message, $user, $body, $internal) {
            $reply = new CustomerMessageReply(['message' => $body, 'is_internal' => $internal]);
            $reply->user()->associate($user);
            $message->replies()->save($reply);
            if (! $internal) {
                $message->update(['status' => CustomerMessageStatus::Answered, 'replied_at' => now()]);
            }

            return $reply;
        });
    }
}
