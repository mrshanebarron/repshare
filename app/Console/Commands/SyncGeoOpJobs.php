<?php

namespace App\Console\Commands;

use App\Contracts\JobServiceInterface;
use App\Models\Job;
use App\Enums\JobStatus;
use App\Events\JobCompleted;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncGeoOpJobs extends Command
{
    protected $signature = 'sync:geoop-jobs {--since= : Sync jobs modified since this datetime}';

    protected $description = 'Sync job updates from GeoOp and process completions';

    public function handle(JobServiceInterface $jobService): int
    {
        $since = $this->option('since')
            ? Carbon::parse($this->option('since'))
            : now()->subHours(2);

        $this->info("Syncing GeoOp jobs since {$since->toDateTimeString()}...");

        try {
            // Get completed jobs from GeoOp
            $completedJobs = $jobService->getCompletedJobs($since);

            $this->info("Found {$completedJobs->count()} completed jobs.");

            $processed = 0;
            foreach ($completedJobs as $jobData) {
                // Find local job record
                $job = Job::where('geoop_id', $jobData->externalId)->first();

                if (!$job) {
                    $this->warn("  Job not found locally: {$jobData->externalId}");
                    continue;
                }

                // Skip if already processed
                if ($job->status === JobStatus::Completed) {
                    continue;
                }

                // Update local job
                $job->update([
                    'status' => JobStatus::Completed,
                    'actual_start' => $jobData->actualStart,
                    'actual_end' => $jobData->actualEnd,
                ]);

                // Sync time entries
                $timeEntries = $jobService->getTimeEntries($jobData->externalId);
                foreach ($timeEntries as $entry) {
                    $job->timeEntries()->updateOrCreate(
                        ['geoop_id' => $entry->externalId],
                        [
                            'producer_id' => $entry->producerId,
                            'start_time' => $entry->startTime,
                            'end_time' => $entry->endTime,
                            'duration_minutes' => $entry->durationMinutes,
                            'description' => $entry->description,
                            'billable' => $entry->billable,
                        ]
                    );
                }

                // Fire job completed event
                event(new JobCompleted($job));

                $processed++;
                $this->info("  Processed job: {$job->id} ({$jobData->externalId})");
            }

            $this->info("Processed {$processed} job completions.");
            Log::info('GeoOp job sync completed', [
                'found' => $completedJobs->count(),
                'processed' => $processed,
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('GeoOp job sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}
