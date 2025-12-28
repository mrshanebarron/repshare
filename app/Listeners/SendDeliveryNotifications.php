<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendDeliveryNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderDelivered $event): void
    {
        $brandOrder = $event->brandOrder;
        $order = $brandOrder->order;
        $venue = $order->venue;
        $brand = $brandOrder->brand;
        $producer = $order->producer;

        Log::info('Sending delivery notifications', ['brand_order_id' => $brandOrder->id]);

        // Notify venue
        $venue->user?->notify(new OrderDeliveredNotification($brandOrder, 'venue'));

        // Notify brand
        $brand->user?->notify(new OrderDeliveredNotification($brandOrder, 'brand'));

        // Notify producer if assigned
        $producer?->user?->notify(new OrderDeliveredNotification($brandOrder, 'producer'));
    }
}
