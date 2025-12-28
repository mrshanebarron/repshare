<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Venue extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'type',
        'description',
        'logo_url',
        'address',
        'city',
        'state',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'contact_name',
        'contact_email',
        'contact_phone',
        'liquor_license',
        'trading_hours',
        'preferred_brands',
        'external_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'trading_hours' => 'array',
        'preferred_brands' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_active', 'type'])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address,
            $this->city,
            $this->state,
            $this->postcode,
        ])->filter()->implode(', ');
    }
}
