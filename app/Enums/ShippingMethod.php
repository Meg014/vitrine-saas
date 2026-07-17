<?php

namespace App\Enums;

enum ShippingMethod: string
{
    case Pickup = 'pickup';
    case LocalDelivery = 'local_delivery';
    case Shipping = 'shipping';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Retirada',self::LocalDelivery => 'Entrega local',self::Shipping => 'Envio'
        };
    }
}
