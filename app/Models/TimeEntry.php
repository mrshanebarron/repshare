<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'producer_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'description',
        'billable',
        'hourly_rate',
        'total_cost',
        'external_id',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    public function calculateDuration(): int
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->diffInMinutes($this->end_time);
        }
        return $this->duration_minutes ?? 0;
    }

    public function calculateCost(): float
    {
        if (!$this->billable || !$this->hourly_rate) {
            return 0;
        }
        return ($this->calculateDuration() / 60) * $this->hourly_rate;
    }

    protected static function booted(): void
    {
        static::saving(function (TimeEntry $entry) {
            if ($entry->start_time && $entry->end_time) {
                $entry->duration_minutes = $entry->calculateDuration();
            }
            $entry->total_cost = $entry->calculateCost();
        });
    }
}
