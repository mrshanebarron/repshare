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

class Brand extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo_url',
        'website',
        'country',
        'region',
        'categories',
        'contact_name',
        'contact_email',
        'contact_phone',
        'commission_rate',
        'platform_fee_percent',
        'external_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'categories' => 'array',
        'metadata' => 'array',
        'commission_rate' => 'decimal:2',
        'platform_fee_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_active', 'commission_rate', 'platform_fee_percent'])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function producers(): BelongsToMany
    {
        return $this->belongsToMany(Producer::class)
            ->withPivot('is_primary', 'started_at')
            ->withTimestamps();
    }

    public function brandOrders(): HasMany
    {
        return $this->hasMany(BrandOrder::class);
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true);
    }
}
