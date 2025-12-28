<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BillingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'job_id',
        'order_id',
        'brand_order_id',
        'producer_id',
        'brand_id',
        'venue_id',
        'description',
        'quantity',
        'unit',
        'rate',
        'amount',
        'status',
        'invoice_id',
        'invoiced_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'invoiced_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function brandOrder(): BelongsTo
    {
        return $this->belongsTo(BrandOrder::class);
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInvoiced($query)
    {
        return $query->where('status', 'invoiced');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function markInvoiced(string $invoiceId): void
    {
        $this->update([
            'status' => 'invoiced',
            'invoice_id' => $invoiceId,
            'invoiced_at' => now(),
        ]);
    }

    public function markPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
