<?php

namespace App\Enums;

enum JobStatus: string
{
    case Scheduled = 'scheduled';
    case EnRoute = 'en_route';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match($this) {
            self::Scheduled => 'Scheduled',
            self::EnRoute => 'En Route',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No Show',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Scheduled => 'blue',
            self::EnRoute => 'amber',
            self::InProgress => 'purple',
            self::Completed => 'green',
            self::Cancelled => 'zinc',
            self::NoShow => 'red',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Scheduled,
            self::EnRoute,
            self::InProgress,
        ]);
    }
}
