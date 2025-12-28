<?php

namespace App\Data;

use App\Enums\JobType;
use App\Enums\JobStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Carbon\Carbon;

class JobData extends Data
{
    public function __construct(
        public ?string $externalId = null,
        public ?int $localId = null,
        #[Required]
        public JobType $type = JobType::SalesVisit,
        public JobStatus $status = JobStatus::Scheduled,
        public ?int $producerId = null,
        public ?string $producerName = null,
        public ?int $venueId = null,
        public ?string $venueName = null,
        public ?string $venueAddress = null,
        public ?float $venueLatitude = null,
        public ?float $venueLongitude = null,
        public ?Carbon $scheduledStart = null,
        public ?Carbon $scheduledEnd = null,
        public ?Carbon $actualStart = null,
        public ?Carbon $actualEnd = null,
        public ?string $notes = null,
        public ?string $completionNotes = null,
        public array $brands = [],
        public array $products = [],
        public array $metadata = [],
    ) {}

    public function duration(): ?int
    {
        if ($this->actualStart && $this->actualEnd) {
            return $this->actualStart->diffInMinutes($this->actualEnd);
        }
        if ($this->scheduledStart && $this->scheduledEnd) {
            return $this->scheduledStart->diffInMinutes($this->scheduledEnd);
        }
        return null;
    }

    public function isOverdue(): bool
    {
        return $this->status->isActive()
            && $this->scheduledEnd
            && $this->scheduledEnd->isPast();
    }
}
