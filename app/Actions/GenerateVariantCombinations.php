<?php

namespace App\Actions;

use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GenerateVariantCombinations
{
    public const MAX_COMBINATIONS = 100;

    public function preview(array $groups): int
    {
        return array_reduce($groups, fn (int $total, array $values) => $total * count($values), 1);
    }

    public function handle(Product $product, array $groups): Collection
    {
        if ($product->product_type->value !== 'variable') {
            throw ValidationException::withMessages(['product' => 'Somente produtos variáveis possuem combinações.']);
        } $count = $this->preview($groups);
        if ($count < 1 || $count > self::MAX_COMBINATIONS) {
            throw ValidationException::withMessages(['variations' => 'A geração deve conter entre 1 e '.self::MAX_COMBINATIONS.' combinações.']);
        } $allowed = $product->attributes()->pluck('attributes.id')->all();
        foreach ($groups as $attributeId => $ids) {
            if (! in_array((int) $attributeId, $allowed, true) || AttributeValue::whereIn('id', $ids)->where('attribute_id', $attributeId)->count() !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['variations' => 'Os valores selecionados não pertencem aos atributos do produto.']);
            }
        } $combinations = [[]];
        foreach ($groups as $ids) {
            $next = [];
            foreach ($combinations as $combo) {
                foreach ($ids as $id) {
                    $next[] = [...$combo, (int) $id];
                }
            } $combinations = $next;
        }

        return collect($combinations)->map(function (array $ids) {
            sort($ids);

            return ['value_ids' => $ids, 'combination_key' => implode('-', $ids)];
        })->unique('combination_key')->values();
    }
}
