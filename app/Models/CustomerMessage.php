<?php

namespace App\Models;

use App\Enums\CustomerMessageSource;
use App\Enums\CustomerMessageStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'name', 'email', 'phone', 'subject', 'message', 'status', 'source', 'read_at', 'replied_at', 'assigned_user_id'])]
class CustomerMessage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => CustomerMessageStatus::class, 'source' => CustomerMessageSource::class, 'read_at' => 'datetime', 'replied_at' => 'datetime'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CustomerMessageReply::class);
    }
}
