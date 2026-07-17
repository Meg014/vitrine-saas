<?php

namespace App\Enums;

enum AttributeDisplayType: string
{
    case Select = 'select';
    case Radio = 'radio';
    case Color = 'color';
    case Button = 'button';
}
