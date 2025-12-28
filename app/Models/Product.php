<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'brand_id',
        'sku',
        'name',
        'slug',
        'description',
        'category',
        'subcategory',
        'unit_price',
        'wholesale_price',
        'rrp',
        'pack_size',
        'case_size',
        'uom',
        'image_url',
        'alcohol_percent',
        'country_of_origin',
        'region',
        'weight_kg',
        'volume_ml',
        'attributes',
        'external_id',
        'unleashed_guid',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'attributes' => 'array',
        'metadata' => 'array',
        'unit_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'rrp' => 'decimal:2',
        'alcohol_percent' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'volume_ml' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'unit_price', 'is_active'])
            ->logOnlyDirty();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function casePrice(): float
    {
        return $this->unit_price * $this->case_size;
    }

    public function totalStockOnHand(): int
    {
        return $this->stockLevels()->sum('quantity_on_hand');
    }

    public function totalStockAvailable(): int
    {
        return $this->stockLevels()->sum('quantity_available');
    }

    public function stockAtWarehouse(int $warehouseId): ?StockLevel
    {
        return $this->stockLevels()->where('warehouse_id', $warehouseId)->first();
    }
}
