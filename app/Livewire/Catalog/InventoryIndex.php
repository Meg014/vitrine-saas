<?php

namespace App\Livewire\Catalog;

use App\Actions\AdjustInventory;
use App\Enums\InventoryMovementType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CurrentStore;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $targetType = null;

    public ?int $targetId = null;

    public string $movementType = 'adjustment';

    public int $quantity = 0;

    public string $reason = '';

    public function adjust(AdjustInventory $action, CurrentStore $current): void
    {
        $data = $this->validate(['targetType' => ['required', Rule::in(['product', 'variant'])], 'targetId' => ['required', 'integer'], 'movementType' => [Rule::enum(InventoryMovementType::class)], 'quantity' => ['required', 'integer', 'min:0'], 'reason' => ['nullable', 'max:255']]);
        $store = $current->getOrFail();
        $item = $data['targetType'] === 'product' ? Product::where('store_id', $store->id)->where('product_type', 'simple')->findOrFail($data['targetId']) : ProductVariant::whereHas('product', fn ($q) => $q->where('store_id', $store->id))->findOrFail($data['targetId']);
        $action->handle($item, InventoryMovementType::from($data['movementType']), $data['quantity'], auth()->user(), $data['reason']);
        $this->reset(['targetType', 'targetId', 'quantity', 'reason']);
        session()->flash('status', 'Estoque atualizado.');
    }

    public function render(CurrentStore $current)
    {
        $store = $current->getOrFail();
        $products = Product::where('store_id', $store->id)->where('product_type', 'simple')->withMax('inventoryMovements', 'created_at')->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))->get()->map(fn ($p) => ['type' => 'product', 'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'stock' => $p->stock_quantity, 'minimum' => $p->low_stock_threshold, 'last' => $p->inventory_movements_max_created_at]);
        $variants = ProductVariant::whereHas('product', fn ($q) => $q->where('store_id', $store->id))->with('product:id,name')->withMax('inventoryMovements', 'created_at')->get()->map(fn ($v) => ['type' => 'variant', 'id' => $v->id, 'name' => $v->product->name.' — '.$v->name, 'sku' => $v->sku, 'stock' => $v->stock_quantity, 'minimum' => $v->low_stock_threshold, 'last' => $v->inventory_movements_max_created_at]);

        return view('livewire.catalog.inventory-index', ['items' => $products->concat($variants)]);
    }
}
