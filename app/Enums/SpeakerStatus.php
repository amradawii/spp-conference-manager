<?php

namespace App\Enums;

enum SpeakerStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case NotAttending = 'not_attending';
}
