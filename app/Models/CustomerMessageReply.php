<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['message', 'is_internal'])]
class CustomerMessageReply extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    public function customerMessage(): BelongsTo
    {
        return $this->belongsTo(CustomerMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
