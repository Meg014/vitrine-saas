<?php

namespace App\Actions;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MergeCarts
{
    public function __construct(private ResolveCart $resolve, private AddCartItem $add) {}

    public function handle(Cart $guest, Customer $customer): Cart
    {
        return DB::transaction(function () use ($guest, $customer) {
            $target = $this->resolve->handle($guest->store, $customer);
            foreach ($guest->items()->with(['product', 'productVariant'])->get() as $item) {
                try {
                    $this->add->handle($target, $item->product, $item->quantity, $item->productVariant);
                } catch (ValidationException) {
                }
            }if (! $guest->is($target)) {
                $guest->update(['status' => CartStatus::Abandoned]);
            }

            return $target;
        });
    }
}
