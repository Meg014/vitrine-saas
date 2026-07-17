<?php

namespace App\Actions;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddCartItem
{
    public function handle(Cart $cart, Product $product, int $quantity, ?ProductVariant $variant = null): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'A quantidade mínima é 1.']);
        }if ($product->store_id !== $cart->store_id || $product->status !== ProductStatus::Active || ! $product->published_at || $product->published_at->isFuture()) {
            throw ValidationException::withMessages(['product' => 'Produto indisponível.']);
        }if ($product->product_type === ProductType::Variable) {
            if (! $variant || $variant->product_id !== $product->id || ! $variant->is_active) {
                throw ValidationException::withMessages(['variant' => 'Selecione uma variação válida.']);
            }$stock = $variant->stock_quantity;
            $price = $variant->price;
            $promo = $variant->promotional_price;
            $sku = $variant->sku;
        } else {
            if ($variant) {
                throw ValidationException::withMessages(['variant' => 'Produto simples não aceita variação.']);
            }$stock = $product->stock_quantity;
            $price = $product->base_price;
            $promo = $product->promotional_price;
            $sku = $product->sku;
        }

        return DB::transaction(function () use ($cart, $product, $quantity, $variant, $stock, $price, $promo, $sku) {
            $item = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->where('product_variant_id', $variant?->id)->lockForUpdate()->first();
            $new = $quantity + ($item?->quantity ?? 0);
            if ($new > $stock) {
                throw ValidationException::withMessages(['quantity' => 'Quantidade maior que o estoque disponível.']);
            }$data = ['product_id' => $product->id, 'product_variant_id' => $variant?->id, 'quantity' => $new, 'unit_price' => $price, 'promotional_unit_price' => $promo, 'product_name_snapshot' => $product->name, 'variant_name_snapshot' => $variant?->name, 'sku_snapshot' => $sku];
            if ($item) {
                $item->update($data);

                return $item;
            }$item = new CartItem($data);
            $cart->items()->save($item);

            return $item;
        });
    }
}
