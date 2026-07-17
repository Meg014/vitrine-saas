<?php

namespace App\Actions;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustInventory
{
    public function handle(Product|ProductVariant $item, InventoryMovementType $type, int $quantity, User $user, ?string $reason = null): InventoryMovement
    {
        return DB::transaction(function () use ($item, $type, $quantity, $user, $reason) {
            $locked = $item::query()->lockForUpdate()->findOrFail($item->id);
            $previous = $locked->stock_quantity;
            $new = $type === InventoryMovementType::Adjustment ? $quantity : $previous + ($type === InventoryMovementType::Exit ? -abs($quantity) : abs($quantity));
            if ($new < 0) {
                throw ValidationException::withMessages(['quantity' => 'O estoque não pode ficar negativo.']);
            } $locked->update(['stock_quantity' => $new]);
            $product = $locked instanceof Product ? $locked : $locked->product;

            return InventoryMovement::create(['store_id' => $product->store_id, 'product_id' => $locked instanceof Product ? $locked->id : null, 'product_variant_id' => $locked instanceof ProductVariant ? $locked->id : null, 'user_id' => $user->id, 'type' => $type, 'quantity' => $new - $previous, 'previous_quantity' => $previous, 'new_quantity' => $new, 'reason' => $reason]);
        });
    }
}
