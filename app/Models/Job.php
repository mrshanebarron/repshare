<?php

namespace App\Models;

use App\Enums\JobType;
use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Job extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'jobs_schedule';

    protected $fillable = [
        'producer_id',
        'venue_id',
        'type',
        'status',
        'title',
        'description',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'duration_minutes',
        'notes',
        'completion_notes',
        'external_id',
        'geoop_id',
        'brands',
        'products',
        'metadata',
    ];

    protected $casts = [
        'type' => JobType::class,
        'status' => JobStatus::class,
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'brands' => 'array',
        'products' => 'array',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'actual_start', 'actual_end'])
            ->logOnlyDirty();
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function start(): void
    {
        $this->status = JobStatus::InProgress;
        $this->actual_start = now();
        $this->save();
    }

    public function complete(?string $notes = null): void
    {
        $this->status = JobStatus::Completed;
        $this->actual_end = now();
        $this->completion_notes = $notes;

        if ($this->actual_start) {
            $this->duration_minutes = $this->actual_start->diffInMinutes($this->actual_end);
        }

        $this->save();
    }

    public function isOverdue(): bool
    {
        return $this->status->isActive()
            && $this->scheduled_end
            && $this->scheduled_end->isPast();
    }

    public function duration(): ?int
    {
        if ($this->duration_minutes) {
            return $this->duration_minutes;
        }
        if ($this->actual_start && $this->actual_end) {
            return $this->actual_start->diffInMinutes($this->actual_end);
        }
        if ($this->scheduled_start && $this->scheduled_end) {
            return $this->scheduled_start->diffInMinutes($this->scheduled_end);
        }
        return null;
    }
}
