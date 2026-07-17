<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Pix = 'pix';
    case BankTransfer = 'bank_transfer';
    case PayOnPickup = 'pay_on_pickup';
    case CashOnDelivery = 'cash_on_delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'PIX',self::BankTransfer => 'Transferência bancária',self::PayOnPickup => 'Pagamento na retirada',self::CashOnDelivery => 'Dinheiro na entrega'
        };
    }
}
