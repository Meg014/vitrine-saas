<?php

namespace App\Livewire\Storefront;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerOrders extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.storefront.customer-orders', ['orders' => Auth::guard('customer')->user()->orders()->latest()->paginate(10)]);
    }
}
