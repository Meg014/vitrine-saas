<?php

namespace App\Actions;

use App\Models\Attribute;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class AttachProductAttributes
{
    public function handle(Product $product, array $attributeIds): void
    {
        if (Attribute::whereIn('id', $attributeIds)->where('store_id', $product->store_id)->count() !== count(array_unique($attributeIds))) {
            throw ValidationException::withMessages(['attributes' => 'Todos os atributos devem pertencer à loja atual.']);
        }$product->attributes()->sync(collect($attributeIds)->mapWithKeys(fn ($id, $order) => [$id => ['sort_order' => $order]])->all());
    }
}
