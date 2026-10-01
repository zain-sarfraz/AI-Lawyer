<?php

namespace App\Enums;

enum AvailabilityExceptionType: string
{
    case Unavailable = 'unavailable';
    case Available = 'available';
}
