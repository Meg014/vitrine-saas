<?php

namespace App\Actions;

use App\Enums\InventoryMovementType;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProduct
{
    public function handle(Store $store, array $data): Product
    {
        $this->validate($store, $data);
        if (($data['product_type'] ?? null) === ProductType::Variable->value) {
            $data['stock_quantity'] = 0;
        }

        return DB::transaction(function () use ($store, $data): Product {
            $product = $store->products()->create($data);
            if ($product->product_type === ProductType::Simple && $product->stock_quantity > 0) {
                $product->inventoryMovements()->create([
                    'store_id' => $store->id,
                    'type' => InventoryMovementType::Initial,
                    'quantity' => $product->stock_quantity,
                    'previous_quantity' => 0,
                    'new_quantity' => $product->stock_quantity,
                    'reason' => 'Estoque inicial',
                ]);
            }

            return $product;
        });
    }

    private function validate(Store $store, array $data): void
    {
        if (isset($data['category_id']) && ! Category::whereKey($data['category_id'])->where('store_id', $store->id)->exists()) {
            throw ValidationException::withMessages(['category_id' => 'A categoria não pertence à loja atual.']);
        } if (($data['promotional_price'] ?? null) !== null && $data['promotional_price'] >= $data['base_price']) {
            throw ValidationException::withMessages(['promotional_price' => 'O preço promocional deve ser menor que o preço normal.']);
        } if (($data['stock_quantity'] ?? 0) < 0) {
            throw ValidationException::withMessages(['stock_quantity' => 'O estoque não pode ser negativo.']);
        }
    }
}
