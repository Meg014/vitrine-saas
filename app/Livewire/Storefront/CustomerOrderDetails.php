<?php

namespace App\Livewire\Storefront;

use App\Actions\CancelOrder;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomerOrderDetails extends Component
{
    public Order $order;

    public function mount(): void
    {
        abort_unless($this->order->customer_id === Auth::guard('customer')->id() && $this->order->store_id === (int) request()->route('store')->id, 404);
    }

    public function cancel(CancelOrder $cancel): void
    {
        $cancel->handle($this->order, customer: Auth::guard('customer')->user());
        $this->order->refresh();
    }

    public function render()
    {
        return view('livewire.storefront.customer-order-details', ['order' => $this->order->load(['items', 'address', 'histories'])]);
    }
}
