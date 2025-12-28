<?php

namespace App\Enums;

enum FulfilmentStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Picking = 'picking';
    case Packed = 'packed';
    case AwaitingCarrier = 'awaiting_carrier';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned to 3PL',
            self::Picking => 'Picking',
            self::Packed => 'Packed',
            self::AwaitingCarrier => 'Awaiting Carrier',
            self::Dispatched => 'Dispatched',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'zinc',
            self::Assigned => 'blue',
            self::Picking => 'amber',
            self::Packed => 'purple',
            self::AwaitingCarrier => 'cyan',
            self::Dispatched => 'indigo',
            self::Delivered => 'green',
            self::Failed => 'red',
        };
    }
}
