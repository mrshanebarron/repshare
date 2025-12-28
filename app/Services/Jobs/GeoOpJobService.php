<?php

namespace App\Services\Jobs;

use App\Contracts\JobServiceInterface;
use App\Data\JobData;
use App\Data\TimeEntryData;
use App\Enums\JobType;
use App\Enums\JobStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Real GeoOp API implementation.
 * Requires GEOOP_API_KEY in .env
 */
class GeoOpJobService implements JobServiceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.geoop.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.geoop.api_key', '');
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->{$method}($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            Log::error('GeoOp API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('GeoOp API error: ' . $response->status());
        }

        return $response->json();
    }

    public function createJob(JobData $data): JobData
    {
        $response = $this->request('post', '/jobs', [
            'job_type' => $this->mapJobType($data->type),
            'client_id' => $data->venueId,
            'assigned_to' => $data->producerId,
            'scheduled_start' => $data->scheduledStart?->toIso8601String(),
            'scheduled_end' => $data->scheduledEnd?->toIso8601String(),
            'notes' => $data->notes,
            'custom_fields' => [
                'brands' => $data->brands,
                'products' => $data->products,
            ],
        ]);

        // Sync to local database
        $job = \App\Models\Job::create([
            'producer_id' => $data->producerId,
            'venue_id' => $data->venueId,
            'type' => $data->type,
            'status' => JobStatus::Scheduled,
            'scheduled_start' => $data->scheduledStart,
            'scheduled_end' => $data->scheduledEnd,
            'notes' => $data->notes,
            'external_id' => $response['id'],
            'geoop_id' => $response['id'],
            'brands' => $data->brands,
            'products' => $data->products,
        ]);

        return new JobData(
            externalId: $response['id'],
            localId: $job->id,
            type: $data->type,
            status: JobStatus::Scheduled,
            producerId: $data->producerId,
            venueId: $data->venueId,
            scheduledStart: $data->scheduledStart,
            scheduledEnd: $data->scheduledEnd,
            notes: $data->notes,
            brands: $data->brands,
            products: $data->products,
        );
    }

    public function updateJob(string $externalId, JobData $data): JobData
    {
        $response = $this->request('put', "/jobs/{$externalId}", [
            'scheduled_start' => $data->scheduledStart?->toIso8601String(),
            'scheduled_end' => $data->scheduledEnd?->toIso8601String(),
            'notes' => $data->notes,
        ]);

        // Sync to local
        \App\Models\Job::where('geoop_id', $externalId)->update([
            'scheduled_start' => $data->scheduledStart,
            'scheduled_end' => $data->scheduledEnd,
            'notes' => $data->notes,
        ]);

        return $this->getJob($externalId);
    }

    public function getJob(string $externalId): ?JobData
    {
        try {
            $response = $this->request('get', "/jobs/{$externalId}");

            return new JobData(
                externalId: $response['id'],
                type: $this->mapGeoOpType($response['job_type']),
                status: $this->mapGeoOpStatus($response['status']),
                producerId: $response['assigned_to'],
                venueId: $response['client_id'],
                venueName: $response['client_name'] ?? null,
                venueAddress: $response['address'] ?? null,
                scheduledStart: isset($response['scheduled_start']) ? Carbon::parse($response['scheduled_start']) : null,
                scheduledEnd: isset($response['scheduled_end']) ? Carbon::parse($response['scheduled_end']) : null,
                actualStart: isset($response['actual_start']) ? Carbon::parse($response['actual_start']) : null,
                actualEnd: isset($response['actual_end']) ? Carbon::parse($response['actual_end']) : null,
                notes: $response['notes'] ?? null,
                brands: $response['custom_fields']['brands'] ?? [],
                products: $response['custom_fields']['products'] ?? [],
            );
        } catch (\Exception $e) {
            Log::error('Failed to get GeoOp job', ['external_id' => $externalId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function getJobsForProducer(int $producerId, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $params = ['assigned_to' => $producerId];
        if ($from) $params['from'] = $from->toIso8601String();
        if ($to) $params['to'] = $to->toIso8601String();

        $response = $this->request('get', '/jobs?' . http_build_query($params));

        return collect($response['data'] ?? [])->map(fn ($job) => new JobData(
            externalId: $job['id'],
            type: $this->mapGeoOpType($job['job_type']),
            status: $this->mapGeoOpStatus($job['status']),
            producerId: $job['assigned_to'],
            venueId: $job['client_id'],
            venueName: $job['client_name'] ?? null,
            scheduledStart: isset($job['scheduled_start']) ? Carbon::parse($job['scheduled_start']) : null,
            scheduledEnd: isset($job['scheduled_end']) ? Carbon::parse($job['scheduled_end']) : null,
        ));
    }

    public function getJobsForVenue(int $venueId, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $params = ['client_id' => $venueId];
        if ($from) $params['from'] = $from->toIso8601String();
        if ($to) $params['to'] = $to->toIso8601String();

        $response = $this->request('get', '/jobs?' . http_build_query($params));

        return collect($response['data'] ?? [])->map(fn ($job) => new JobData(
            externalId: $job['id'],
            type: $this->mapGeoOpType($job['job_type']),
            status: $this->mapGeoOpStatus($job['status']),
            producerId: $job['assigned_to'],
            venueId: $job['client_id'],
            scheduledStart: isset($job['scheduled_start']) ? Carbon::parse($job['scheduled_start']) : null,
            scheduledEnd: isset($job['scheduled_end']) ? Carbon::parse($job['scheduled_end']) : null,
        ));
    }

    public function getJobsByStatus(JobStatus $status): Collection
    {
        $response = $this->request('get', '/jobs?status=' . $this->mapStatusToGeoOp($status));

        return collect($response['data'] ?? [])->map(fn ($job) => new JobData(
            externalId: $job['id'],
            type: $this->mapGeoOpType($job['job_type']),
            status: $this->mapGeoOpStatus($job['status']),
            producerId: $job['assigned_to'],
            venueId: $job['client_id'],
            scheduledStart: isset($job['scheduled_start']) ? Carbon::parse($job['scheduled_start']) : null,
            scheduledEnd: isset($job['scheduled_end']) ? Carbon::parse($job['scheduled_end']) : null,
        ));
    }

    public function getCompletedJobs(Carbon $since): Collection
    {
        return $this->getJobsByStatus(JobStatus::Completed)
            ->filter(fn ($job) => $job->actualEnd && $job->actualEnd->gte($since));
    }

    public function startJob(string $externalId): JobData
    {
        $this->request('post', "/jobs/{$externalId}/start");

        \App\Models\Job::where('geoop_id', $externalId)->update([
            'status' => JobStatus::InProgress,
            'actual_start' => now(),
        ]);

        return $this->getJob($externalId);
    }

    public function completeJob(string $externalId, ?string $notes = null): JobData
    {
        $this->request('post', "/jobs/{$externalId}/complete", [
            'notes' => $notes,
        ]);

        \App\Models\Job::where('geoop_id', $externalId)->update([
            'status' => JobStatus::Completed,
            'actual_end' => now(),
            'completion_notes' => $notes,
        ]);

        return $this->getJob($externalId);
    }

    public function cancelJob(string $externalId, string $reason): JobData
    {
        $this->request('post', "/jobs/{$externalId}/cancel", [
            'reason' => $reason,
        ]);

        \App\Models\Job::where('geoop_id', $externalId)->update([
            'status' => JobStatus::Cancelled,
            'completion_notes' => $reason,
        ]);

        return $this->getJob($externalId);
    }

    public function getTimeEntries(string $externalId): Collection
    {
        $response = $this->request('get', "/jobs/{$externalId}/time-entries");

        return collect($response['data'] ?? [])->map(fn ($entry) => new TimeEntryData(
            externalId: $entry['id'],
            jobExternalId: $externalId,
            producerId: $entry['user_id'],
            startTime: Carbon::parse($entry['start_time']),
            endTime: isset($entry['end_time']) ? Carbon::parse($entry['end_time']) : null,
            durationMinutes: $entry['duration_minutes'] ?? null,
            description: $entry['description'] ?? null,
            billable: $entry['billable'] ?? true,
        ));
    }

    public function addTimeEntry(string $externalId, TimeEntryData $entry): TimeEntryData
    {
        $response = $this->request('post', "/jobs/{$externalId}/time-entries", [
            'start_time' => $entry->startTime?->toIso8601String(),
            'end_time' => $entry->endTime?->toIso8601String(),
            'duration_minutes' => $entry->durationMinutes,
            'description' => $entry->description,
            'billable' => $entry->billable,
        ]);

        return new TimeEntryData(
            externalId: $response['id'],
            jobExternalId: $externalId,
            startTime: $entry->startTime,
            endTime: $entry->endTime,
            durationMinutes: $entry->durationMinutes,
            description: $entry->description,
            billable: $entry->billable,
        );
    }

    public function syncJobStatus(string $externalId): JobStatus
    {
        $job = $this->getJob($externalId);
        return $job?->status ?? JobStatus::Scheduled;
    }

    public function getPendingEvents(): Collection
    {
        // Would poll GeoOp webhook events
        return collect();
    }

    public function acknowledgeEvent(string $eventId): bool
    {
        return true;
    }

    private function mapJobType(JobType $type): string
    {
        return match($type) {
            JobType::Tasting => 'tasting',
            JobType::SalesVisit => 'sales_visit',
            JobType::Circuit => 'circuit',
            JobType::Delivery => 'delivery',
            JobType::Pickup => 'pickup',
        };
    }

    private function mapGeoOpType(string $type): JobType
    {
        return match($type) {
            'tasting' => JobType::Tasting,
            'sales_visit' => JobType::SalesVisit,
            'circuit' => JobType::Circuit,
            'delivery' => JobType::Delivery,
            'pickup' => JobType::Pickup,
            default => JobType::SalesVisit,
        };
    }

    private function mapGeoOpStatus(string $status): JobStatus
    {
        return match($status) {
            'scheduled' => JobStatus::Scheduled,
            'en_route' => JobStatus::EnRoute,
            'in_progress' => JobStatus::InProgress,
            'completed' => JobStatus::Completed,
            'cancelled' => JobStatus::Cancelled,
            'no_show' => JobStatus::NoShow,
            default => JobStatus::Scheduled,
        };
    }

    private function mapStatusToGeoOp(JobStatus $status): string
    {
        return $status->value;
    }
}
