<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case ReadyForPickup = 'ready_for_pickup';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::ReadyForPickup => 'Ready for Pickup',
            self::InTransit => 'In Transit',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft => 'zinc',
            self::Pending => 'amber',
            self::Confirmed => 'blue',
            self::Processing => 'indigo',
            self::ReadyForPickup => 'purple',
            self::InTransit => 'cyan',
            self::Delivered => 'green',
            self::Cancelled => 'red',
            self::Refunded => 'rose',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Confirmed,
            self::Processing,
            self::ReadyForPickup,
            self::InTransit,
        ]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::Delivered,
            self::Cancelled,
            self::Refunded,
        ]);
    }
}
