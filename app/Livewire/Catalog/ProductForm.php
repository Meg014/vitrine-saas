<?php

namespace App\Livewire\Catalog;

use App\Actions\AttachProductAttributes;
use App\Actions\CreateProduct;
use App\Actions\CreateProductVariants;
use App\Actions\GenerateVariantCombinations;
use App\Actions\StoreProductImages;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Support\CurrentStore;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductForm extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    public string $slug = '';

    public ?int $categoryId = null;

    public string $shortDescription = '';

    public string $description = '';

    public string $status = 'draft';

    public string $productType = 'simple';

    public string $basePrice = '0,00';

    public ?string $promotionalPrice = null;

    public ?string $costPrice = null;

    public ?string $sku = null;

    public ?string $barcode = null;

    public bool $trackInventory = true;

    public int $stockQuantity = 0;

    public ?int $lowStockThreshold = null;

    public ?int $weight = null;

    public ?int $height = null;

    public ?int $width = null;

    public ?int $length = null;

    public bool $isFeatured = false;

    public array $images = [];

    public array $selectedAttributes = [];

    public array $selectedValues = [];

    public array $variantRows = [];

    public int $combinationEstimate = 0;

    public function mount(?Product $product = null): void
    {
        if ($product) {
            $this->product = $product;
            $this->authorize('update', $product);
            foreach (['name', 'slug', 'status', 'sku', 'barcode', 'weight', 'height', 'width', 'length'] as $field) {
                $this->{$field} = $product->{$field};
            }$this->categoryId = $product->category_id;
            $this->shortDescription = $product->short_description ?? '';
            $this->description = $product->description ?? '';
            $this->productType = $product->product_type->value;
            $this->basePrice = $this->money($product->base_price);
            $this->promotionalPrice = $product->promotional_price !== null ? $this->money($product->promotional_price) : null;
            $this->costPrice = $product->cost_price !== null ? $this->money($product->cost_price) : null;
            $this->trackInventory = $product->track_inventory;
            $this->stockQuantity = $product->stock_quantity;
            $this->lowStockThreshold = $product->low_stock_threshold;
            $this->isFeatured = $product->is_featured;
            $this->selectedAttributes = $product->attributes()->pluck('attributes.id')->all();
        }
    }

    public function updatedName(): void
    {
        if (! $this->product) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function estimate(GenerateVariantCombinations $generator): void
    {
        $groups = array_filter($this->selectedValues);
        $this->combinationEstimate = $groups ? $generator->preview($groups) : 0;
        if ($this->combinationEstimate > 100) {
            $this->addError('selectedValues', 'O limite é de 100 combinações.');
        } elseif ($this->combinationEstimate > 0) {
            $this->variantRows = array_map(fn (int $index): array => [
                'sku' => Str::upper($this->slug).'-'.($index + 1),
                'price' => $this->cents($this->basePrice),
                'stock_quantity' => 0,
                'is_active' => true,
            ], range(0, $this->combinationEstimate - 1));
        }
    }

    public function save(CurrentStore $current, CreateProduct $create, AttachProductAttributes $attach, CreateProductVariants $variants, StoreProductImages $imageAction)
    {
        $store = $current->getOrFail();
        $this->authorize($this->product ? 'update' : 'create', $this->product ?? Product::class);
        $data = $this->validate(['name' => ['required', 'max:150'], 'slug' => ['required', 'alpha_dash:ascii', Rule::unique('products')->where('store_id', $store->id)->ignore($this->product?->id)], 'categoryId' => ['nullable', Rule::exists('categories', 'id')->where('store_id', $store->id)], 'status' => [Rule::enum(ProductStatus::class)], 'productType' => [Rule::enum(ProductType::class)], 'basePrice' => ['required'], 'promotionalPrice' => ['nullable'], 'costPrice' => ['nullable'], 'sku' => ['nullable', 'max:100', Rule::unique('products')->where('store_id', $store->id)->ignore($this->product?->id)], 'stockQuantity' => ['integer', 'min:0'], 'lowStockThreshold' => ['nullable', 'integer', 'min:0'], 'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $payload = ['name' => $data['name'], 'slug' => $data['slug'], 'category_id' => $data['categoryId'], 'short_description' => $this->shortDescription ?: null, 'description' => $this->description ?: null, 'status' => $data['status'], 'product_type' => $data['productType'], 'base_price' => $this->cents($data['basePrice']), 'promotional_price' => $this->nullableCents($data['promotionalPrice']), 'cost_price' => $this->nullableCents($data['costPrice']), 'sku' => $data['sku'] ?: null, 'barcode' => $this->barcode ?: null, 'track_inventory' => $this->trackInventory, 'stock_quantity' => $data['productType'] === 'simple' ? $data['stockQuantity'] : 0, 'low_stock_threshold' => $data['lowStockThreshold'], 'weight' => $this->weight, 'height' => $this->height, 'width' => $this->width, 'length' => $this->length, 'is_featured' => $this->isFeatured, 'published_at' => $data['status'] === 'active' ? now() : null];
        $product = $this->product;
        if ($product) {
            if ($payload['category_id'] && ! Category::whereKey($payload['category_id'])->where('store_id', $store->id)->exists()) {
                $this->addError('categoryId', 'Categoria inválida.');
            }$product->update($payload);
        } else {
            $product = $create->handle($store, $payload);
        }$attach->handle($product, $this->selectedAttributes);
        if ($product->product_type === ProductType::Variable && $this->selectedValues && $this->variantRows) {
            $variants->handle($product, array_filter($this->selectedValues), $this->variantRows);
        }if ($this->images) {
            $imageAction->handle($product, $this->images);
        }session()->flash('status', 'Produto salvo com sucesso.');

        return redirect()->route('products.edit', $product);
    }

    private function cents(string $value): int
    {
        return (int) round((float) str_replace(',', '.', str_replace('.', '', $value)) * 100);
    }

    private function nullableCents(?string $value): ?int
    {
        return blank($value) ? null : $this->cents($value);
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }

    public function render(CurrentStore $current)
    {
        $store = $current->getOrFail();

        return view('livewire.catalog.product-form', ['categories' => Category::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get(), 'attributes' => Attribute::where('store_id', $store->id)->where('is_active', true)->with(['values' => fn ($q) => $q->where('is_active', true)])->orderBy('sort_order')->get()]);
    }
}
