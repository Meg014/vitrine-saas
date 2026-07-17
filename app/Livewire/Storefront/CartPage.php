<?php

namespace App\Livewire\Storefront;

use App\Actions\ResolveCart;
use App\Actions\UpdateCartItem;
use App\Support\PublicStoreContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CartPage extends Component
{
    public function updateQuantity(int $id, int $quantity, ResolveCart $resolve, UpdateCartItem $update, PublicStoreContext $c): void
    {
        $cart = $resolve->handle($c->getOrFail(), Auth::guard('customer')->user());
        $item = $cart->items()->with(['product', 'productVariant'])->findOrFail($id);
        $update->handle($item, $quantity);
    }

    public function remove(int $id, ResolveCart $resolve, PublicStoreContext $c): void
    {
        $resolve->handle($c->getOrFail(), Auth::guard('customer')->user())->items()->findOrFail($id)->delete();
    }

    public function render(ResolveCart $resolve, PublicStoreContext $c)
    {
        $cart = $resolve->handle($c->getOrFail(), Auth::guard('customer')->user());
        $cart->load('items.product.primaryImage');

        return view('livewire.storefront.cart-page', compact('cart'));
    }
}
