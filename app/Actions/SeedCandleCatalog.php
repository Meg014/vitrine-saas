<?php

namespace App\Actions;

use App\Enums\AttributeDisplayType;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedCandleCatalog
{
    public function handle(Store $store): void
    {
        DB::transaction(function () use ($store) {
            foreach (['Velas aromáticas', 'Velas decorativas', 'Kits para presente', 'Acessórios'] as $i => $name) {
                $store->categories()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i]);
            }$groups = ['Aroma' => ['Lavanda', 'Baunilha', 'Canela', 'Capim-limão', 'Flor de cerejeira'], 'Cor' => ['Branca', 'Rosa', 'Lilás', 'Amarela', 'Verde'], 'Tamanho' => ['100 g', '200 g', '300 g'], 'Tipo de cera' => ['Soja', 'Coco', 'Parafina', 'Cera de abelha']];
            $colors = ['Branca' => '#FFFFFF', 'Rosa' => '#FFC0CB', 'Lilás' => '#C8A2C8', 'Amarela' => '#FFFF00', 'Verde' => '#008000'];
            $order = 0;
            foreach ($groups as $name => $values) {
                $attribute = $store->attributes()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'display_type' => $name === 'Cor' ? AttributeDisplayType::Color : AttributeDisplayType::Select, 'sort_order' => $order++]);
                foreach ($values as $i => $value) {
                    $attribute->values()->firstOrCreate(['name' => $value], ['color_hex' => $colors[$value] ?? null, 'sort_order' => $i]);
                }
            }
            $category = $store->categories()->where('slug', 'velas-aromaticas')->first() ?? $store->categories()->first();
            foreach ([
                ['Vela de Lavanda', 'vela-lavanda', 4990, 3990, 'VELA-LAV', true],
                ['Vela de Baunilha', 'vela-baunilha', 4590, null, 'VELA-BAU', false],
                ['Kit Presente Aromático', 'kit-presente-aromatico', 8990, 7990, 'KIT-ARO', true],
            ] as [$name, $slug, $price, $promo, $sku, $featured]) {
                $store->products()->firstOrCreate(['slug' => $slug], ['category_id' => $category?->id, 'name' => $name, 'short_description' => 'Produzida artesanalmente para tornar seus momentos mais acolhedores.', 'status' => ProductStatus::Active, 'product_type' => ProductType::Simple, 'base_price' => $price, 'promotional_price' => $promo, 'sku' => $sku, 'stock_quantity' => 20, 'is_featured' => $featured, 'published_at' => now()]);
            }
        });
    }
}
