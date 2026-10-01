<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case ConsultationBooked = 'consultation_booked';
    case ConsultationCompleted = 'consultation_completed';
    case Converted = 'converted';
    case Lost = 'lost';
    case FollowUp = 'follow_up';
}
