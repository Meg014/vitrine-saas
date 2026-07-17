<?php

namespace App\Enums;

enum CustomerMessageSource: string
{
    case ContactForm = 'contact_form';
    case ProductQuestion = 'product_question';
    case OrderQuestion = 'order_question';
    case Manual = 'manual';
}
