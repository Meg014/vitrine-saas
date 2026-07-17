<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'phone', 'document', 'birth_date', 'notes', 'status', 'accepts_marketing', 'last_purchase_at', 'password'])]
#[Hidden(['password', 'remember_token'])]
class Customer extends Authenticatable
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => CustomerStatus::class, 'birth_date' => 'date', 'accepts_marketing' => 'boolean', 'last_purchase_at' => 'datetime', 'email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CustomerMessage::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
}
