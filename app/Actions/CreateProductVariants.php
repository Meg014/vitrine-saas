<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProductVariants
{
    public function __construct(private GenerateVariantCombinations $generator) {}

    public function handle(Product $product, array $groups, array $rows): void
    {
        $combinations = $this->generator->handle($product, $groups);
        DB::transaction(function () use ($product, $combinations, $rows) {
            foreach ($combinations as $index => $combo) {
                $row = $rows[$index] ?? [];
                $sku = $row['sku'] ?? null;
                if (! $sku) {
                    throw ValidationException::withMessages(['variants' => 'Informe o SKU da variação '.($index + 1).'.']);
                }if (Product::where('store_id', $product->store_id)->where('sku', $sku)->exists() || ProductVariant::whereHas('product', fn ($q) => $q->where('store_id', $product->store_id))->where('sku', $sku)->exists()) {
                    throw ValidationException::withMessages(['variants' => "O SKU {$sku} já está em uso nesta loja."]);
                }$price = (int) ($row['price'] ?? 0);
                $promo = $row['promotional_price'] ?? null;
                if ($promo !== null && (int) $promo >= $price) {
                    throw ValidationException::withMessages(['variants' => 'O preço promocional deve ser menor que o normal.']);
                }$variant = $product->variants()->create([...$row, 'price' => $price, 'stock_quantity' => (int) ($row['stock_quantity'] ?? 0), 'combination_key' => $combo['combination_key']]);
                $variant->attributeValues()->attach($combo['value_ids']);
            }
        });
    }
}
