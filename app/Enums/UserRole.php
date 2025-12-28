<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Brand = 'brand';
    case Venue = 'venue';
    case Producer = 'producer';
    case ThreePL = '3pl';

    public function label(): string
    {
        return match($this) {
            self::Admin => 'Administrator',
            self::Brand => 'Brand Owner',
            self::Venue => 'Venue',
            self::Producer => 'Sales Rep',
            self::ThreePL => '3PL Provider',
        };
    }

    public function dashboardRoute(): string
    {
        return match($this) {
            self::Admin => 'admin.dashboard',
            self::Brand => 'brand.dashboard',
            self::Venue => 'venue.dashboard',
            self::Producer => 'producer.dashboard',
            self::ThreePL => 'threePL.dashboard',
        };
    }
}
