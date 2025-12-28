<?php

namespace App\Services\Jobs;

use App\Contracts\JobServiceInterface;
use App\Data\JobData;
use App\Data\TimeEntryData;
use App\Enums\JobType;
use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\TimeEntry;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Dummy implementation of JobService that uses local database.
 * Replace with GeoOpJobService when API credentials are available.
 */
class DummyJobService implements JobServiceInterface
{
    public function createJob(JobData $data): JobData
    {
        $job = Job::create([
            'producer_id' => $data->producerId,
            'venue_id' => $data->venueId,
            'type' => $data->type,
            'status' => $data->status,
            'title' => $data->venueName . ' - ' . $data->type->label(),
            'scheduled_start' => $data->scheduledStart,
            'scheduled_end' => $data->scheduledEnd,
            'notes' => $data->notes,
            'brands' => $data->brands,
            'products' => $data->products,
            'metadata' => $data->metadata,
        ]);

        return $this->jobToData($job);
    }

    public function updateJob(string $externalId, JobData $data): JobData
    {
        $job = Job::where('external_id', $externalId)->firstOrFail();

        $job->update([
            'type' => $data->type,
            'status' => $data->status,
            'scheduled_start' => $data->scheduledStart,
            'scheduled_end' => $data->scheduledEnd,
            'notes' => $data->notes,
            'brands' => $data->brands,
            'products' => $data->products,
        ]);

        return $this->jobToData($job->fresh());
    }

    public function getJob(string $externalId): ?JobData
    {
        $job = Job::with(['producer', 'venue'])
            ->where('external_id', $externalId)
            ->orWhere('id', $externalId)
            ->first();

        return $job ? $this->jobToData($job) : null;
    }

    public function getJobsForProducer(int $producerId, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = Job::with(['producer', 'venue'])
            ->where('producer_id', $producerId);

        if ($from) {
            $query->where('scheduled_start', '>=', $from);
        }
        if ($to) {
            $query->where('scheduled_start', '<=', $to);
        }

        return $query->orderBy('scheduled_start')
            ->get()
            ->map(fn (Job $job) => $this->jobToData($job));
    }

    public function getJobsForVenue(int $venueId, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = Job::with(['producer', 'venue'])
            ->where('venue_id', $venueId);

        if ($from) {
            $query->where('scheduled_start', '>=', $from);
        }
        if ($to) {
            $query->where('scheduled_start', '<=', $to);
        }

        return $query->orderBy('scheduled_start')
            ->get()
            ->map(fn (Job $job) => $this->jobToData($job));
    }

    public function getJobsByStatus(JobStatus $status): Collection
    {
        return Job::with(['producer', 'venue'])
            ->where('status', $status)
            ->orderBy('scheduled_start')
            ->get()
            ->map(fn (Job $job) => $this->jobToData($job));
    }

    public function getCompletedJobs(Carbon $since): Collection
    {
        return Job::with(['producer', 'venue'])
            ->where('status', JobStatus::Completed)
            ->where('actual_end', '>=', $since)
            ->orderBy('actual_end', 'desc')
            ->get()
            ->map(fn (Job $job) => $this->jobToData($job));
    }

    public function startJob(string $externalId): JobData
    {
        $job = Job::where('external_id', $externalId)
            ->orWhere('id', $externalId)
            ->firstOrFail();

        $job->start();

        return $this->jobToData($job->fresh());
    }

    public function completeJob(string $externalId, ?string $notes = null): JobData
    {
        $job = Job::where('external_id', $externalId)
            ->orWhere('id', $externalId)
            ->firstOrFail();

        $job->complete($notes);

        // Update producer stats
        $job->producer->increment('completed_jobs_count');

        return $this->jobToData($job->fresh());
    }

    public function cancelJob(string $externalId, string $reason): JobData
    {
        $job = Job::where('external_id', $externalId)
            ->orWhere('id', $externalId)
            ->firstOrFail();

        $job->update([
            'status' => JobStatus::Cancelled,
            'completion_notes' => $reason,
        ]);

        return $this->jobToData($job->fresh());
    }

    public function getTimeEntries(string $externalId): Collection
    {
        $job = Job::where('external_id', $externalId)
            ->orWhere('id', $externalId)
            ->first();

        if (!$job) {
            return collect();
        }

        return $job->timeEntries
            ->map(fn (TimeEntry $entry) => new TimeEntryData(
                externalId: $entry->external_id,
                jobExternalId: $job->external_id ?? (string) $job->id,
                producerId: $entry->producer_id,
                startTime: $entry->start_time,
                endTime: $entry->end_time,
                durationMinutes: $entry->duration_minutes,
                description: $entry->description,
                billable: $entry->billable,
                hourlyRate: (float) $entry->hourly_rate,
            ));
    }

    public function addTimeEntry(string $externalId, TimeEntryData $entry): TimeEntryData
    {
        $job = Job::where('external_id', $externalId)
            ->orWhere('id', $externalId)
            ->firstOrFail();

        $timeEntry = TimeEntry::create([
            'job_id' => $job->id,
            'producer_id' => $entry->producerId ?? $job->producer_id,
            'start_time' => $entry->startTime,
            'end_time' => $entry->endTime,
            'duration_minutes' => $entry->durationMinutes,
            'description' => $entry->description,
            'billable' => $entry->billable,
            'hourly_rate' => $entry->hourlyRate ?? $job->producer->hourly_rate,
        ]);

        return new TimeEntryData(
            externalId: (string) $timeEntry->id,
            jobExternalId: $job->external_id ?? (string) $job->id,
            producerId: $timeEntry->producer_id,
            startTime: $timeEntry->start_time,
            endTime: $timeEntry->end_time,
            durationMinutes: $timeEntry->duration_minutes,
            description: $timeEntry->description,
            billable: $timeEntry->billable,
            hourlyRate: (float) $timeEntry->hourly_rate,
        );
    }

    public function syncJobStatus(string $externalId): JobStatus
    {
        $job = Job::where('external_id', $externalId)
            ->orWhere('id', $externalId)
            ->first();

        return $job?->status ?? JobStatus::Scheduled;
    }

    public function getPendingEvents(): Collection
    {
        // Dummy implementation - no external events to process
        return collect();
    }

    public function acknowledgeEvent(string $eventId): bool
    {
        // Dummy implementation
        return true;
    }

    private function jobToData(Job $job): JobData
    {
        return new JobData(
            externalId: $job->external_id ?? (string) $job->id,
            localId: $job->id,
            type: $job->type,
            status: $job->status,
            producerId: $job->producer_id,
            producerName: $job->producer?->name,
            venueId: $job->venue_id,
            venueName: $job->venue?->name,
            venueAddress: $job->venue?->fullAddress(),
            venueLatitude: $job->venue?->latitude,
            venueLongitude: $job->venue?->longitude,
            scheduledStart: $job->scheduled_start,
            scheduledEnd: $job->scheduled_end,
            actualStart: $job->actual_start,
            actualEnd: $job->actual_end,
            notes: $job->notes,
            completionNotes: $job->completion_notes,
            brands: $job->brands ?? [],
            products: $job->products ?? [],
            metadata: $job->metadata ?? [],
        );
    }
}
