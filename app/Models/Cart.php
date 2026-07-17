<?php

namespace App\Models;

use App\Enums\CartStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'session_id', 'status', 'expires_at'])]
class Cart extends Model
{
    protected function casts(): array
    {
        return ['status' => CartStatus::class, 'expires_at' => 'datetime'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function subtotal(): int
    {
        return (int) $this->items->sum(fn (CartItem $i) => ($i->promotional_unit_price ?? $i->unit_price) * $i->quantity);
    }
}
