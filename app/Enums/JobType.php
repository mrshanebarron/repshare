<?php

namespace App\Enums;

enum JobType: string
{
    case Tasting = 'tasting';
    case SalesVisit = 'sales_visit';
    case Circuit = 'circuit';
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match($this) {
            self::Tasting => 'Tasting',
            self::SalesVisit => 'Sales Visit',
            self::Circuit => 'Circuit Day',
            self::Delivery => 'Delivery',
            self::Pickup => 'Pickup',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Tasting => 'beaker',
            self::SalesVisit => 'briefcase',
            self::Circuit => 'map',
            self::Delivery => 'truck',
            self::Pickup => 'inbox-arrow-down',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Tasting => 'purple',
            self::SalesVisit => 'blue',
            self::Circuit => 'green',
            self::Delivery => 'amber',
            self::Pickup => 'cyan',
        };
    }
}
