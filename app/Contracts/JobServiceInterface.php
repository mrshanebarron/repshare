<?php

namespace App\Contracts;

use App\Data\JobData;
use App\Data\TimeEntryData;
use App\Enums\JobType;
use App\Enums\JobStatus;
use Illuminate\Support\Collection;
use Carbon\Carbon;

interface JobServiceInterface
{
    /**
     * Create a new job in the external system
     */
    public function createJob(JobData $data): JobData;

    /**
     * Update an existing job
     */
    public function updateJob(string $externalId, JobData $data): JobData;

    /**
     * Get a job by its external ID
     */
    public function getJob(string $externalId): ?JobData;

    /**
     * Get all jobs for a producer
     * @return Collection<int, JobData>
     */
    public function getJobsForProducer(int $producerId, ?Carbon $from = null, ?Carbon $to = null): Collection;

    /**
     * Get all jobs for a venue
     * @return Collection<int, JobData>
     */
    public function getJobsForVenue(int $venueId, ?Carbon $from = null, ?Carbon $to = null): Collection;

    /**
     * Get jobs by status
     * @return Collection<int, JobData>
     */
    public function getJobsByStatus(JobStatus $status): Collection;

    /**
     * Get completed jobs since a given time
     * @return Collection<int, JobData>
     */
    public function getCompletedJobs(Carbon $since): Collection;

    /**
     * Start a job (check-in)
     */
    public function startJob(string $externalId): JobData;

    /**
     * Complete a job (check-out)
     */
    public function completeJob(string $externalId, ?string $notes = null): JobData;

    /**
     * Cancel a job
     */
    public function cancelJob(string $externalId, string $reason): JobData;

    /**
     * Get time entries for a job
     * @return Collection<int, TimeEntryData>
     */
    public function getTimeEntries(string $externalId): Collection;

    /**
     * Add a time entry to a job
     */
    public function addTimeEntry(string $externalId, TimeEntryData $entry): TimeEntryData;

    /**
     * Sync job status from external system
     */
    public function syncJobStatus(string $externalId): JobStatus;

    /**
     * Get pending webhooks/events from external system
     * @return Collection
     */
    public function getPendingEvents(): Collection;

    /**
     * Acknowledge an event as processed
     */
    public function acknowledgeEvent(string $eventId): bool;
}
