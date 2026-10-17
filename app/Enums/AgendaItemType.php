<?php

namespace App\Enums;

enum AgendaItemType: string
{
    case Address = 'address';
    case Keynote = 'keynote';
    case Oral = 'oral';
    case Segment = 'segment';

    public function hasSpeaker(): bool
    {
        return $this !== self::Segment;
    }
}
