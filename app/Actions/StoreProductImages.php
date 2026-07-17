<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StoreProductImages
{
    public function handle(Product $product, array $files): void
    {
        if ($product->images()->count() + count($files) > 10) {
            throw ValidationException::withMessages(['images' => 'O produto pode ter no máximo 10 imagens.']);
        } $stored = [];
        try {
            DB::transaction(function () use ($product, $files, &$stored) {
                $primary = ! $product->images()->exists();
                foreach ($files as $index => $file) {
                    $path = $file->store("stores/{$product->store_id}/products/{$product->id}", 'public');
                    $stored[] = $path;
                    $product->images()->create(['path' => $path, 'sort_order' => $product->images()->count(), 'is_primary' => $primary && $index === 0]);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($stored);
            throw $e;
        }
    }

    public function delete(ProductImage $image): void
    {
        DB::transaction(function () use ($image) {
            $was = $image->is_primary;
            $product = $image->product;
            $path = $image->path;
            $image->delete();
            Storage::disk('public')->delete($path);
            if ($was) {
                $product->images()->first()?->update(['is_primary' => true]);
            }
        });
    }

    public function setPrimary(ProductImage $image): void
    {
        DB::transaction(function () use ($image) {
            $image->product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });
    }
}
