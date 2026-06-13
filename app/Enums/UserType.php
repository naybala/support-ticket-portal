<?php

namespace App\Enums;

enum UserType: string
{
    case Agent = 'agent';
    case Employee = 'employee';

    public function color(): string
    {
        return match ($this) {
            self::Agent => 'text-yellow-500',
            self::Employee => 'text-blue-500',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Agent => 'Agent',
            self::Employee => 'Employee',
        };
    }
}