<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case ReadyForPickup = 'ready_for_pickup';
    case Fulfilled = 'fulfilled';
    case Canceled = 'canceled';
}
