<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Support\CurrentStore;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $marketing = 'all';

    public string $sort = 'created_at';

    public function render(CurrentStore $current)
    {
        $store = $current->getOrFail();
        $customers = Customer::where('store_id', $store->id)->withCount('addresses')->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%")->orWhere('document', 'like', "%{$this->search}%")))->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))->when($this->marketing !== 'all', fn ($q) => $q->where('accepts_marketing', $this->marketing === 'yes'))->orderBy(in_array($this->sort, ['name', 'created_at', 'last_purchase_at'], true) ? $this->sort : 'created_at', $this->sort === 'name' ? 'asc' : 'desc')->paginate(15);

        return view('livewire.customers.customer-index', compact('customers'));
    }
}
