<?php

namespace App\Models;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['customer_id', 'cart_id', 'sequence_number', 'number', 'checkout_token', 'status', 'payment_status', 'fulfillment_status', 'payment_method', 'shipping_method', 'subtotal', 'shipping_amount', 'discount_amount', 'total', 'currency', 'customer_notes', 'internal_notes', 'customer_name', 'customer_email', 'customer_phone', 'customer_document', 'tracking_code', 'paid_at', 'confirmed_at', 'canceled_at', 'stock_restored_at'])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => OrderStatus::class, 'payment_status' => PaymentStatus::class, 'fulfillment_status' => FulfillmentStatus::class, 'payment_method' => PaymentMethod::class, 'shipping_method' => ShippingMethod::class, 'paid_at' => 'datetime', 'confirmed_at' => 'datetime', 'canceled_at' => 'datetime', 'stock_restored_at' => 'datetime'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }
}
