<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case ONE_TIME = 'one_time';
    case RECURRING = 'recurring';
}
