<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'brand_order_id',
        'product_id',
        'sku',
        'product_name',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_amount',
        'line_total',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'metadata' => 'array',
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

    protected static function booted(): void
    {
        static::saving(function (OrderLine $line) {
            $subtotal = $line->quantity * $line->unit_price;
            $line->discount_amount = $subtotal * ($line->discount_percent / 100);
            $line->line_total = $subtotal - $line->discount_amount;
        });
    }
}
