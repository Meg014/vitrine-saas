<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',self::Paid => 'Pago',self::Failed => 'Falhou',self::Refunded => 'Reembolsado',self::Canceled => 'Cancelado'
        };
    }
}
