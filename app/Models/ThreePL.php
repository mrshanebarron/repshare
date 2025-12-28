<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ThreePL extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'three_pls';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'code',
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
        'service_areas',
        'capabilities',
        'base_handling_fee',
        'per_case_fee',
        'external_id',
        'unleashed_warehouse_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'service_areas' => 'array',
        'capabilities' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'base_handling_fee' => 'decimal:2',
        'per_case_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_active', 'base_handling_fee', 'per_case_fee'])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'three_pl_id');
    }

    public function brandOrders(): HasMany
    {
        return $this->hasMany(BrandOrder::class, 'three_pl_id');
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
