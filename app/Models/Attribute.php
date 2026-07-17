<?php

namespace App\Models;

use App\Enums\AttributeDisplayType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'display_type', 'is_required', 'is_active', 'sort_order'])]
class Attribute extends Model
{
    protected function casts(): array
    {
        return ['display_type' => AttributeDisplayType::class, 'is_required' => 'boolean', 'is_active' => 'boolean'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attribute')->withPivot('sort_order');
    }
}
