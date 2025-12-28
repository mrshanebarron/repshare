<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'three_pl_id',
        'code',
        'name',
        'address',
        'city',
        'state',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'external_id',
        'unleashed_guid',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function threePL(): BelongsTo
    {
        return $this->belongsTo(ThreePL::class, 'three_pl_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function brandOrders(): HasMany
    {
        return $this->hasMany(BrandOrder::class);
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
