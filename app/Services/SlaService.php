<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class SlaService
{
    /**
     * Calculate SLA due date based on priority.
     */
    public function calculate(string $priority): Carbon
    {
        return match ($priority) {
            'high' => now()->addHours(4),
            'normal' => now()->addDay(),
            'low' => now()->addDays(3),
            default => now()->addDay(),
        };
    }
}
