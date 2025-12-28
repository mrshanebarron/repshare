<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\FulfilmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BrandOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'order_number',
        'order_id',
        'brand_id',
        'warehouse_id',
        'three_pl_id',
        'status',
        'fulfilment_status',
        'subtotal',
        'discount_total',
        'tax_total',
        'commission_amount',
        'platform_fee',
        'grand_total',
        'net_to_brand',
        'tracking_number',
        'carrier',
        'carrier_service',
        'shipping_cost',
        'picked_at',
        'packed_at',
        'packer_name',
        'packing_notes',
        'dispatched_at',
        'delivered_at',
        'delivery_proof',
        'signature_name',
        'external_id',
        'unleashed_order_id',
        'alm_order_id',
        'alm_status',
        'alm_tracking_number',
        'alm_carrier',
        'alm_submitted_at',
        'alm_shipped_at',
        'metadata',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'fulfilment_status' => FulfilmentStatus::class,
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'net_to_brand' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'picked_at' => 'datetime',
        'packed_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'alm_submitted_at' => 'datetime',
        'alm_shipped_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'fulfilment_status', 'tracking_number'])
            ->logOnlyDirty();
    }

    protected static function booted(): void
    {
        static::creating(function (BrandOrder $order) {
            if (!$order->order_number) {
                $order->order_number = 'BRD-' . strtoupper(uniqid());
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function threePL(): BelongsTo
    {
        return $this->belongsTo(ThreePL::class, 'three_pl_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
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

        // Calculate commission and fees
        $this->commission_amount = $this->subtotal * ($this->brand->commission_rate / 100);
        $this->platform_fee = $this->subtotal * ($this->brand->platform_fee_percent / 100);
        $this->grand_total = $this->subtotal + $this->tax_total - $this->discount_total;
        $this->net_to_brand = $this->grand_total - $this->commission_amount - $this->platform_fee;

        $this->save();
    }

    public function markDispatched(string $carrier, string $trackingNumber): void
    {
        $this->carrier = $carrier;
        $this->tracking_number = $trackingNumber;
        $this->fulfilment_status = FulfilmentStatus::Dispatched;
        $this->dispatched_at = now();
        $this->save();
    }

    public function markDelivered(): void
    {
        $this->fulfilment_status = FulfilmentStatus::Delivered;
        $this->status = OrderStatus::Delivered;
        $this->delivered_at = now();
        $this->save();
    }
}
