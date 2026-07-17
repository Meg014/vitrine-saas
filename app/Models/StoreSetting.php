<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['primary_color', 'secondary_color', 'accent_color', 'font_family', 'currency', 'locale', 'timezone', 'hero_title', 'hero_subtitle', 'hero_image_path', 'hero_button_text', 'hero_button_url', 'about_title', 'about_text'])]
class StoreSetting extends Model
{
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
