<?php

namespace App\Listeners;

use App\Events\JobCompleted;
use App\Models\Order;
use App\Models\BillingRecord;
use App\Notifications\OrderPromptNotification;
use App\Notifications\ReviewRequestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessJobCompletion implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(JobCompleted $event): void
    {
        $job = $event->job;

        Log::info('Processing job completion', ['job_id' => $job->id, 'type' => $job->type]);

        // 1. Create billing record for producer time
        $this->createBillingRecord($job);

        // 2. Prompt for order if applicable
        $this->promptForOrder($job);

        // 3. Request review from venue
        $this->requestReview($job);
    }

    private function createBillingRecord($job): void
    {
        $producer = $job->producer;
        $venue = $job->venue;

        if (!$producer) {
            return;
        }

        // Calculate billable amount based on time entries
        $totalMinutes = $job->timeEntries()->sum('duration_minutes');
        $hourlyRate = $producer->hourly_rate ?? config('services.platform.default_hourly_rate', 50);
        $amount = ($totalMinutes / 60) * $hourlyRate;

        if ($amount <= 0) {
            return;
        }

        BillingRecord::create([
            'type' => 'producer_time',
            'job_id' => $job->id,
            'producer_id' => $producer->id,
            'venue_id' => $venue?->id,
            'description' => "Time for job #{$job->id} - {$job->type->value}",
            'quantity' => $totalMinutes,
            'unit' => 'minutes',
            'rate' => $hourlyRate,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        Log::info('Billing record created', [
            'job_id' => $job->id,
            'producer_id' => $producer->id,
            'minutes' => $totalMinutes,
            'amount' => $amount,
        ]);
    }

    private function promptForOrder($job): void
    {
        $producer = $job->producer;
        $venue = $job->venue;

        if (!$producer || !$venue) {
            return;
        }

        // Check if an order was already created for this job
        $existingOrder = Order::where('job_id', $job->id)->exists();

        if (!$existingOrder) {
            // Notify producer to create order
            $producer->user?->notify(new OrderPromptNotification($job));
        }
    }

    private function requestReview($job): void
    {
        $venue = $job->venue;

        if (!$venue) {
            return;
        }

        // Send review request to venue
        $venue->user?->notify(new ReviewRequestNotification($job));
    }
}
