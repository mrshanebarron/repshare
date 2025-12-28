<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Carrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'tracking_url_template',
        'is_active',
        'services',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'services' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTrackingUrl(string $trackingNumber): ?string
    {
        if (!$this->tracking_url_template) {
            return null;
        }

        return str_replace('{tracking}', $trackingNumber, $this->tracking_url_template);
    }

    public static function getOptions(): array
    {
        return static::active()
            ->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();
    }
}
