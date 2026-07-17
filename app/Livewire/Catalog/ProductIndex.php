<?php

namespace App\Livewire\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Support\CurrentStore;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $type = 'all';

    public ?int $category = null;

    public bool $lowStock = false;

    public string $sort = 'updated_at';

    public function render(CurrentStore $current)
    {
        $store = $current->getOrFail();
        $products = Product::where('store_id', $store->id)->with(['category', 'primaryImage'])->withCount('variants')->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))->when($this->type !== 'all', fn ($q) => $q->where('product_type', $this->type))->when($this->category, fn ($q) => $q->where('category_id', $this->category))->when($this->lowStock, fn ($q) => $q->whereColumn('stock_quantity', '<=', 'low_stock_threshold'))->orderBy(in_array($this->sort, ['name', 'updated_at', 'base_price'], true) ? $this->sort : 'updated_at', $this->sort === 'name' ? 'asc' : 'desc')->paginate(15);

        return view('livewire.catalog.product-index', ['products' => $products, 'categories' => Category::where('store_id', $store->id)->orderBy('name')->get()]);
    }
}
