<?php

namespace App\Notifications;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRequestNotification extends Notification implements ShouldQueue
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
        $producer = $this->job->producer;

        return (new MailMessage)
            ->subject('How was your ' . $this->job->type->value . '?')
            ->greeting('Thanks for using RepShare!')
            ->line("{$producer?->name} recently completed a {$this->job->type->value} at your venue.")
            ->line('We\'d love to hear about your experience.')
            ->action('Leave a Review', url("/venue/reviews/create/{$this->job->id}"))
            ->line('Your feedback helps us maintain quality service.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_request',
            'job_id' => $this->job->id,
            'producer_id' => $this->job->producer_id,
            'producer_name' => $this->job->producer?->name,
            'job_type' => $this->job->type->value,
            'message' => "Rate your experience with {$this->job->producer?->name}",
        ];
    }
}
