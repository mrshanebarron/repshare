<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Carbon\Carbon;

class TimeEntryData extends Data
{
    public function __construct(
        public ?string $externalId = null,
        public ?string $jobExternalId = null,
        public ?int $producerId = null,
        public ?Carbon $startTime = null,
        public ?Carbon $endTime = null,
        public ?int $durationMinutes = null,
        public ?string $description = null,
        public bool $billable = true,
        public ?float $hourlyRate = null,
        public array $metadata = [],
    ) {}

    public function calculateDuration(): int
    {
        if ($this->durationMinutes) {
            return $this->durationMinutes;
        }
        if ($this->startTime && $this->endTime) {
            return $this->startTime->diffInMinutes($this->endTime);
        }
        return 0;
    }

    public function totalCost(): float
    {
        if (!$this->billable || !$this->hourlyRate) {
            return 0;
        }
        return ($this->calculateDuration() / 60) * $this->hourlyRate;
    }
}
