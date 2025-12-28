<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Enums\JobStatus;
use App\Events\JobCompleted;
use App\Contracts\JobServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GeoOpWebhookController extends Controller
{
    public function __construct(
        private JobServiceInterface $jobService,
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        Log::info('GeoOp webhook received', [
            'event' => $event,
            'job_id' => $data['job_id'] ?? null,
        ]);

        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::warning('GeoOp webhook signature verification failed');
            return response('Unauthorized', 401);
        }

        return match ($event) {
            'job.created' => $this->handleJobCreated($data),
            'job.updated' => $this->handleJobUpdated($data),
            'job.started' => $this->handleJobStarted($data),
            'job.completed' => $this->handleJobCompleted($data),
            'job.cancelled' => $this->handleJobCancelled($data),
            'time_entry.created' => $this->handleTimeEntryCreated($data),
            default => response('Event not handled', 200),
        };
    }

    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-GeoOp-Signature');
        $secret = config('services.geoop.webhook_secret');

        if (!$signature || !$secret) {
            return true; // Skip verification if not configured
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expectedSignature, $signature);
    }

    private function handleJobCreated(array $data): Response
    {
        $externalId = $data['job_id'] ?? null;

        if (!$externalId) {
            return response('Missing job_id', 400);
        }

        // Fetch full job details from GeoOp
        $jobData = $this->jobService->getJob($externalId);

        if (!$jobData) {
            return response('Job not found in GeoOp', 404);
        }

        // Check if job already exists locally
        $job = Job::where('geoop_id', $externalId)->first();

        if ($job) {
            return response('Job already exists', 200);
        }

        // Create local job record
        Job::create([
            'producer_id' => $jobData->producerId,
            'venue_id' => $jobData->venueId,
            'type' => $jobData->type,
            'status' => JobStatus::Scheduled,
            'scheduled_start' => $jobData->scheduledStart,
            'scheduled_end' => $jobData->scheduledEnd,
            'notes' => $jobData->notes,
            'geoop_id' => $externalId,
            'brands' => $jobData->brands,
            'products' => $jobData->products,
        ]);

        Log::info('Job created from webhook', ['geoop_id' => $externalId]);

        return response('Job created', 201);
    }

    private function handleJobUpdated(array $data): Response
    {
        $externalId = $data['job_id'] ?? null;
        $job = Job::where('geoop_id', $externalId)->first();

        if (!$job) {
            return response('Job not found', 404);
        }

        // Fetch updated job details
        $jobData = $this->jobService->getJob($externalId);

        if ($jobData) {
            $job->update([
                'scheduled_start' => $jobData->scheduledStart,
                'scheduled_end' => $jobData->scheduledEnd,
                'notes' => $jobData->notes,
            ]);
        }

        return response('Job updated', 200);
    }

    private function handleJobStarted(array $data): Response
    {
        $externalId = $data['job_id'] ?? null;
        $job = Job::where('geoop_id', $externalId)->first();

        if (!$job) {
            return response('Job not found', 404);
        }

        $job->update([
            'status' => JobStatus::InProgress,
            'actual_start' => $data['started_at'] ?? now(),
        ]);

        Log::info('Job started from webhook', ['job_id' => $job->id]);

        return response('Job started', 200);
    }

    private function handleJobCompleted(array $data): Response
    {
        $externalId = $data['job_id'] ?? null;
        $job = Job::where('geoop_id', $externalId)->first();

        if (!$job) {
            return response('Job not found', 404);
        }

        // Skip if already completed
        if ($job->status === JobStatus::Completed) {
            return response('Job already completed', 200);
        }

        $job->update([
            'status' => JobStatus::Completed,
            'actual_end' => $data['completed_at'] ?? now(),
            'completion_notes' => $data['notes'] ?? null,
        ]);

        // Sync time entries
        $timeEntries = $this->jobService->getTimeEntries($externalId);
        foreach ($timeEntries as $entry) {
            $job->timeEntries()->updateOrCreate(
                ['geoop_id' => $entry->externalId],
                [
                    'producer_id' => $entry->producerId ?? $job->producer_id,
                    'start_time' => $entry->startTime,
                    'end_time' => $entry->endTime,
                    'duration_minutes' => $entry->durationMinutes,
                    'description' => $entry->description,
                    'billable' => $entry->billable,
                ]
            );
        }

        // Fire job completed event
        event(new JobCompleted($job, $data['notes'] ?? null));

        Log::info('Job completed from webhook', ['job_id' => $job->id]);

        return response('Job completed', 200);
    }

    private function handleJobCancelled(array $data): Response
    {
        $externalId = $data['job_id'] ?? null;
        $job = Job::where('geoop_id', $externalId)->first();

        if (!$job) {
            return response('Job not found', 404);
        }

        $job->update([
            'status' => JobStatus::Cancelled,
            'completion_notes' => $data['reason'] ?? 'Cancelled via GeoOp',
        ]);

        Log::info('Job cancelled from webhook', ['job_id' => $job->id]);

        return response('Job cancelled', 200);
    }

    private function handleTimeEntryCreated(array $data): Response
    {
        $jobExternalId = $data['job_id'] ?? null;
        $job = Job::where('geoop_id', $jobExternalId)->first();

        if (!$job) {
            return response('Job not found', 404);
        }

        $job->timeEntries()->updateOrCreate(
            ['geoop_id' => $data['time_entry_id']],
            [
                'producer_id' => $data['user_id'] ?? $job->producer_id,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'description' => $data['description'] ?? null,
                'billable' => $data['billable'] ?? true,
            ]
        );

        return response('Time entry created', 201);
    }
}
