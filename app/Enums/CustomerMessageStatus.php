<?php

namespace App\Enums;

enum CustomerMessageStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case Closed = 'closed';
}
