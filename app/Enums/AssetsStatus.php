<?php

namespace App\Enums;

enum AssetsStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case NotNeeded = 'not_needed';
}
