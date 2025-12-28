<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'quantity_on_order',
        'reorder_point',
        'reorder_quantity',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function recalculateAvailable(): void
    {
        $this->quantity_available = max(0, $this->quantity_on_hand - $this->quantity_reserved);
        $this->save();
    }

    public function hasStock(int $quantity): bool
    {
        return $this->quantity_available >= $quantity;
    }

    public function reserve(int $quantity): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }

        $this->quantity_reserved += $quantity;
        $this->recalculateAvailable();

        return true;
    }

    public function release(int $quantity): void
    {
        $this->quantity_reserved = max(0, $this->quantity_reserved - $quantity);
        $this->recalculateAvailable();
    }

    public function commit(int $quantity): void
    {
        $this->quantity_on_hand -= $quantity;
        $this->quantity_reserved = max(0, $this->quantity_reserved - $quantity);
        $this->recalculateAvailable();
    }
}
