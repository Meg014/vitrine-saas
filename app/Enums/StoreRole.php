<?php

namespace App\Enums;

enum StoreRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Attendant = 'attendant';
    case Inventory = 'inventory';
}
