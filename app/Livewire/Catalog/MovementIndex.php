<?php

namespace App\Livewire\Catalog;

use App\Models\InventoryMovement;
use App\Support\CurrentStore;
use Livewire\Component;
use Livewire\WithPagination;

class MovementIndex extends Component
{
    use WithPagination;

    public function render(CurrentStore $current)
    {
        return view('livewire.catalog.movement-index', ['movements' => InventoryMovement::where('store_id', $current->getOrFail()->id)->with(['product:id,name', 'productVariant:id,product_id,name,sku', 'user:id,name'])->latest()->paginate(25)]);
    }
}
