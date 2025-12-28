<?php

namespace App\Notifications;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPromptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Job $job,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $venue = $this->job->venue;

        return (new MailMessage)
            ->subject('Create Order - Job Completed at ' . $venue?->name)
            ->greeting('Job completed!')
            ->line("Your {$this->job->type->value} at {$venue?->name} has been completed.")
            ->line('Would you like to create an order based on this visit?')
            ->action('Create Order', url("/producer/jobs/{$this->job->id}/order"))
            ->line('You can also access this from your dashboard.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_prompt',
            'job_id' => $this->job->id,
            'venue_id' => $this->job->venue_id,
            'venue_name' => $this->job->venue?->name,
            'job_type' => $this->job->type->value,
            'message' => "Create order for {$this->job->venue?->name}?",
        ];
    }
}
