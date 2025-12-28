<?php

namespace App\Notifications;

use App\Models\BrandOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDeliveredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BrandOrder $brandOrder,
        public string $recipientType = 'venue',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->brandOrder->order;

        $message = (new MailMessage)
            ->subject("Order {$this->brandOrder->order_number} Delivered");

        if ($this->recipientType === 'venue') {
            $message
                ->greeting('Your order has arrived!')
                ->line("Order {$this->brandOrder->order_number} from {$this->brandOrder->brand?->name} has been delivered.")
                ->action('View Order', url("/venue/orders/{$order->id}"));
        } elseif ($this->recipientType === 'brand') {
            $message
                ->greeting('Order Delivered')
                ->line("Order {$this->brandOrder->order_number} has been delivered to {$order->venue?->name}.")
                ->action('View Order', url("/brand/orders/{$this->brandOrder->id}"));
        } else {
            $message
                ->greeting('Order Delivered')
                ->line("Order {$this->brandOrder->order_number} for your job at {$order->venue?->name} has been delivered.")
                ->action('View Order', url("/producer/orders/{$order->id}"));
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_delivered',
            'brand_order_id' => $this->brandOrder->id,
            'order_id' => $this->brandOrder->order_id,
            'order_number' => $this->brandOrder->order_number,
            'brand_name' => $this->brandOrder->brand?->name,
            'tracking_number' => $this->brandOrder->tracking_number,
            'message' => "Order {$this->brandOrder->order_number} delivered",
        ];
    }
}
