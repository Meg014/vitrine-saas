<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',self::Confirmed => 'Confirmado',self::Processing => 'Em preparo',self::Shipped => 'Enviado',self::Completed => 'Concluído',self::Canceled => 'Cancelado'
        };
    }
}
