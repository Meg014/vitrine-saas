<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Initial = 'initial';
    case Entry = 'entry';
    case Exit = 'exit';
    case Adjustment = 'adjustment';
}
