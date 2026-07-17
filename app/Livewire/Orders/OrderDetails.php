<?php

namespace App\Livewire\Orders;

use App\Actions\CancelOrder;
use App\Actions\TransitionOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderDetails extends Component
{
    public Order $order;

    public string $nextStatus = '';

    public string $comment = '';

    public string $trackingCode = '';

    public function mount(): void
    {
        $this->trackingCode = $this->order->tracking_code ?? '';
    }

    public function transition(TransitionOrder $action): void
    {
        $this->validate(['nextStatus' => 'required']);
        $action->status($this->order, OrderStatus::from($this->nextStatus), Auth::user(), $this->comment);
        $this->order->refresh();
        $this->reset('nextStatus', 'comment');
    }

    public function markPaid(TransitionOrder $action): void
    {
        $action->markPaid($this->order, Auth::user());
        $this->order->refresh();
    }

    public function cancel(CancelOrder $action): void
    {
        $action->handle($this->order, Auth::user(), comment: $this->comment);
        $this->order->refresh();
    }

    public function saveTracking(): void
    {
        $this->order->update(['tracking_code' => $this->trackingCode ?: null]);
    }

    public function render()
    {
        return view('livewire.orders.order-details', ['order' => $this->order->load(['items', 'address', 'histories.user'])]);
    }
}
