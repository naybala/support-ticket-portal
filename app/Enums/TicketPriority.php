<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function color(): string
    {
        return match ($this) {
            self::Low => 'dark',
            self::Medium => 'yellow',
            self::High => 'red',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public static function toOptions(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'name' => $case->label(),
        ], self::cases());
    }

    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 48,
            self::Medium => 24,
            self::High => 1,
        };
    }
}