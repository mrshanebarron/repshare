<?php

namespace App\Providers;

use App\Events\JobCompleted;
use App\Events\OrderConfirmed;
use App\Events\OrderDelivered;
use App\Listeners\ProcessJobCompletion;
use App\Listeners\SubmitOrderToWholesale;
use App\Listeners\SendDeliveryNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JobCompleted::class => [
            ProcessJobCompletion::class,
        ],
        OrderConfirmed::class => [
            SubmitOrderToWholesale::class,
        ],
        OrderDelivered::class => [
            SendDeliveryNotifications::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
