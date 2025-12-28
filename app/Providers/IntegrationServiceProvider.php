<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\InventoryServiceInterface;
use App\Contracts\JobServiceInterface;
use App\Services\Inventory\DummyInventoryService;
use App\Services\Inventory\UnleashedInventoryService;
use App\Services\Jobs\DummyJobService;
use App\Services\Jobs\GeoOpJobService;

class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Inventory Service (Unleashed)
        $this->app->singleton(InventoryServiceInterface::class, function ($app) {
            $driver = config('services.inventory.driver', 'dummy');

            return match ($driver) {
                'unleashed' => new UnleashedInventoryService(),
                default => new DummyInventoryService(),
            };
        });

        // Job Service (GeoOp)
        $this->app->singleton(JobServiceInterface::class, function ($app) {
            $driver = config('services.jobs.driver', 'dummy');

            return match ($driver) {
                'geoop' => new GeoOpJobService(),
                default => new DummyJobService(),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
