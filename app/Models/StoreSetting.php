<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['primary_color', 'secondary_color', 'accent_color', 'font_family', 'currency', 'locale', 'timezone', 'hero_title', 'hero_subtitle', 'hero_image_path', 'hero_button_text', 'hero_button_url', 'about_title', 'about_text', 'pickup_enabled', 'pickup_instructions', 'local_delivery_enabled', 'local_delivery_fee', 'local_delivery_instructions', 'shipping_enabled', 'default_shipping_fee', 'shipping_instructions', 'minimum_order_amount', 'payment_methods'])]
class StoreSetting extends Model
{
    protected function casts(): array
    {
        return ['pickup_enabled' => 'boolean', 'local_delivery_enabled' => 'boolean', 'shipping_enabled' => 'boolean', 'payment_methods' => 'array'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
