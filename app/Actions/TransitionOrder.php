<?php

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TransitionOrder
{
    public function status(Order $order, OrderStatus $to, User $user, ?string $comment = null): Order
    {
        $allowed = [OrderStatus::Pending->value => [OrderStatus::Confirmed], OrderStatus::Confirmed->value => [OrderStatus::Processing], OrderStatus::Processing->value => [OrderStatus::Shipped, OrderStatus::Completed], OrderStatus::Shipped->value => [OrderStatus::Completed]];
        if (! in_array($to, $allowed[$order->status->value] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Transição de status inválida.']);
        }$from = $order->status;
        $order->status = $to;
        if ($to === OrderStatus::Confirmed) {
            $order->confirmed_at = now();
        }$order->fulfillment_status = match ($to) {
            OrderStatus::Processing => FulfillmentStatus::Processing,OrderStatus::Shipped => FulfillmentStatus::Shipped,OrderStatus::Completed => FulfillmentStatus::Fulfilled,default => $order->fulfillment_status
        };
        $order->save();
        $order->histories()->create(['user_id' => $user->id, 'from_status' => $from->value, 'to_status' => $to->value, 'comment' => $comment]);

        return $order;
    }

    public function markPaid(Order $order, User $user): Order
    {
        if ($order->status === OrderStatus::Canceled) {
            throw ValidationException::withMessages(['payment' => 'Pedido cancelado não pode ser pago.']);
        }$order->update(['payment_status' => PaymentStatus::Paid, 'paid_at' => now()]);
        $order->histories()->create(['user_id' => $user->id, 'from_status' => $order->status->value, 'to_status' => $order->status->value, 'comment' => 'Pagamento marcado como recebido.']);

        return $order;
    }
}
