<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'brand_order_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'status',
        'expires_at',
        'committed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'committed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function brandOrder(): BelongsTo
    {
        return $this->belongsTo(BrandOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function commit(): void
    {
        $this->status = 'committed';
        $this->committed_at = now();
        $this->save();

        // Update stock level
        $stockLevel = StockLevel::where('product_id', $this->product_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->first();

        if ($stockLevel) {
            $stockLevel->commit($this->quantity);
        }
    }

    public function release(): void
    {
        $this->status = 'released';
        $this->save();

        // Release from stock level
        $stockLevel = StockLevel::where('product_id', $this->product_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->first();

        if ($stockLevel) {
            $stockLevel->release($this->quantity);
        }
    }
}
