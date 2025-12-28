<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Order extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'order_number',
        'venue_id',
        'producer_id',
        'job_id',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'platform_fee',
        'grand_total',
        'notes',
        'delivery_address',
        'delivery_city',
        'delivery_state',
        'delivery_postcode',
        'requested_delivery_date',
        'confirmed_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'requested_delivery_date' => 'date',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'grand_total'])
            ->logOnlyDirty();
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (!$order->order_number) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function brandOrders(): HasMany
    {
        return $this->hasMany(BrandOrder::class);
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->lines()->sum('line_total');
        $this->discount_total = $this->lines()->sum('discount_amount');
        $this->tax_total = $this->lines()->sum('tax_amount');
        $this->platform_fee = $this->subtotal * 0.05; // 5% platform fee
        $this->grand_total = $this->subtotal + $this->tax_total - $this->discount_total;
        $this->save();
    }

    public function confirm(): void
    {
        $this->status = OrderStatus::Confirmed;
        $this->confirmed_at = now();
        $this->save();
    }

    public function complete(): void
    {
        $this->status = OrderStatus::Delivered;
        $this->completed_at = now();
        $this->save();
    }

    public function deliveryAddress(): string
    {
        return collect([
            $this->delivery_address,
            $this->delivery_city,
            $this->delivery_state,
            $this->delivery_postcode,
        ])->filter()->implode(', ');
    }
}
