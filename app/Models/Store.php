<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'slug', 'description', 'email', 'phone', 'document', 'status', 'logo_path'])]
class Store extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => StoreStatus::class];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(StoreSetting::class);
    }
}
