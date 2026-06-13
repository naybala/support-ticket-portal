<?php

namespace App\Enums;

enum SlaStatus: string
{
    case OnTrack = 'on_track';
    case DueSoon = 'due_soon';
    case Overdue = 'overdue';
    case Completed = 'completed';

    public function color(): string
    {
        return match ($this) {
            self::OnTrack => 'green',
            self::DueSoon => 'yellow',
            self::Overdue => 'red',
            self::Completed => 'dark',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OnTrack => 'On Track',
            self::DueSoon => 'Due Soon',
            self::Overdue => 'Overdue',
            self::Completed => 'Completed',
        };
    }
}