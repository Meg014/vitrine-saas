<?php

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    public function handle(Order $order, ?User $user = null, ?Customer $customer = null, ?string $comment = null): Order
    {
        return DB::transaction(function () use ($order, $user, $customer, $comment) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->status === OrderStatus::Canceled) {
                return $order;
            }if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Confirmed], true)) {
                throw ValidationException::withMessages(['status' => 'Este pedido não pode mais ser cancelado.']);
            }if (! $order->stock_restored_at) {
                foreach ($order->items()->get() as $item) {
                    $target = $item->product_variant_id ? ProductVariant::lockForUpdate()->find($item->product_variant_id) : Product::lockForUpdate()->find($item->product_id);
                    if (! $target) {
                        continue;
                    }$previous = $target->stock_quantity;
                    $target->increment('stock_quantity', $item->quantity);
                    InventoryMovement::create(['store_id' => $order->store_id, 'product_id' => $item->product_id, 'product_variant_id' => $item->product_variant_id, 'user_id' => $user?->id, 'type' => InventoryMovementType::Entry, 'quantity' => $item->quantity, 'previous_quantity' => $previous, 'new_quantity' => $previous + $item->quantity, 'reason' => 'Cancelamento '.$order->number, 'reference_type' => Order::class, 'reference_id' => $order->id]);
                }$order->stock_restored_at = now();
            }$from = $order->status;
            $order->status = OrderStatus::Canceled;
            $order->fulfillment_status = FulfillmentStatus::Canceled;
            if ($order->payment_status === PaymentStatus::Pending) {
                $order->payment_status = PaymentStatus::Canceled;
            }$order->canceled_at = now();
            $order->save();
            $order->histories()->create(['user_id' => $user?->id, 'customer_id' => $customer?->id, 'from_status' => $from->value, 'to_status' => OrderStatus::Canceled->value, 'comment' => $comment ?: 'Pedido cancelado.']);

            return $order;
        }, 3);
    }
}
