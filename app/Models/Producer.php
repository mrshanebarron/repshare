<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Producer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'bio',
        'photo_url',
        'phone',
        'city',
        'state',
        'postcode',
        'latitude',
        'longitude',
        'hourly_rate',
        'commission_percent',
        'service_areas',
        'certifications',
        'availability',
        'max_jobs_per_day',
        'rating',
        'completed_jobs_count',
        'external_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'service_areas' => 'array',
        'certifications' => 'array',
        'availability' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'hourly_rate' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_active', 'hourly_rate', 'commission_percent'])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class)
            ->withPivot('is_primary', 'started_at')
            ->withTimestamps();
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function primaryBrands(): BelongsToMany
    {
        return $this->brands()->wherePivot('is_primary', true);
    }
}
