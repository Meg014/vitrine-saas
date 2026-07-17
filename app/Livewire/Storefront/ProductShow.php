<?php

namespace App\Livewire\Storefront;

use App\Actions\AddCartItem;
use App\Actions\ResolveCart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\PublicStoreContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProductShow extends Component
{
    public Product $product;

    public int $quantity = 1;

    public ?int $variantId = null;

    public function add(ResolveCart $resolve, AddCartItem $add, PublicStoreContext $c): void
    {
        $variant = $this->variantId ? ProductVariant::where('product_id', $this->product->id)->findOrFail($this->variantId) : null;
        $cart = $resolve->handle($c->getOrFail(), Auth::guard('customer')->user());
        $add->handle($cart, $this->product, $this->quantity, $variant);
        session()->flash('cart_status', 'Produto adicionado ao carrinho.');
        $this->dispatch('cart-updated');
    }

    public function render(PublicStoreContext $c)
    {
        $this->product->loadMissing(['images', 'category', 'variants.attributeValues.attribute', 'attributes.values']);
        $related = Product::where('store_id', $c->getOrFail()->id)->where('category_id', $this->product->category_id)->whereKeyNot($this->product->id)->where('status', 'active')->whereNotNull('published_at')->limit(4)->get();

        return view('livewire.storefront.product-show', compact('related'));
    }
}
