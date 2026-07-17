<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Support\PublicStoreContext;
use Livewire\Component;

class Home extends Component
{
    public function render(PublicStoreContext $c)
    {
        $s = $c->getOrFail();
        $base = Product::where('store_id', $s->id)->where('status', 'active')->whereNotNull('published_at')->where('published_at', '<=', now())->with('primaryImage');

        return view('livewire.storefront.home', ['featured' => (clone $base)->where('is_featured', true)->latest()->limit(8)->get(), 'recent' => (clone $base)->latest()->limit(8)->get(), 'categories' => Category::where('store_id', $s->id)->whereNull('parent_id')->where('is_active', true)->withCount('products')->limit(8)->get()]);
    }
}
