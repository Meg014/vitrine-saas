<?php

namespace App\Actions;

use App\Models\CartItem;
use Illuminate\Validation\ValidationException;

class UpdateCartItem
{
    public function handle(CartItem $item, int $quantity): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'A quantidade mínima é 1.']);
        }$stock = $item->productVariant?->stock_quantity ?? $item->product->stock_quantity;
        if ($quantity > $stock) {
            throw ValidationException::withMessages(['quantity' => 'Quantidade maior que o estoque disponível.']);
        }$item->update(['quantity' => $quantity]);

        return $item;
    }
}
