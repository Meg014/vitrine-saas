<?php

namespace App\Livewire\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\CurrentStore;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OrderIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updated($name): void
    {
        if (in_array($name, ['search', 'status'])) {
            $this->resetPage();
        }
    }

    public function render(CurrentStore $c)
    {
        $orders = Order::where('store_id', $c->getOrFail()->id)->with('customer')->when($this->search, fn ($q) => $q->where(fn ($x) => $x->where('number', 'like', '%'.$this->search.'%')->orWhere('customer_name', 'like', '%'.$this->search.'%')))->when($this->status, fn ($q) => $q->where('status', $this->status))->latest()->paginate(15);

        return view('livewire.orders.order-index', ['orders' => $orders, 'statuses' => OrderStatus::cases()]);
    }
}
