<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Support\PublicStoreContext;
use Livewire\Component;
use Livewire\WithPagination;

class Catalog extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $categoryId = null;

    public ?int $minPrice = null;

    public ?int $maxPrice = null;

    public bool $available = false;

    public string $sort = 'recent';

    public function mount(?int $categoryId = null): void
    {
        $this->categoryId = $categoryId;
    }

    public function render(PublicStoreContext $c)
    {
        $s = $c->getOrFail();
        $q = Product::where('store_id', $s->id)->where('status', 'active')->whereNotNull('published_at')->where('published_at', '<=', now())->with(['primaryImage', 'category'])->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('short_description', 'like', "%{$this->search}%")))->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))->when($this->minPrice, fn ($q) => $q->where('base_price', '>=', $this->minPrice))->when($this->maxPrice, fn ($q) => $q->where('base_price', '<=', $this->maxPrice))->when($this->available, fn ($q) => $q->where(fn ($q) => $q->where('stock_quantity', '>', 0)->orWhereHas('variants', fn ($v) => $v->where('stock_quantity', '>', 0))));
        match ($this->sort) {
            'price_asc' => $q->orderBy('base_price'),'price_desc' => $q->orderByDesc('base_price'),'name' => $q->orderBy('name'),default => $q->latest()
        };

        return view('livewire.storefront.catalog', ['products' => $q->paginate(16), 'categories' => Category::where('store_id', $s->id)->where('is_active', true)->orderBy('name')->get()]);
    }
}
