<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\InventoryServiceInterface;
use App\Contracts\JobServiceInterface;
use App\Contracts\WholesaleServiceInterface;
use App\Services\Inventory\DummyInventoryService;
use App\Services\Inventory\UnleashedInventoryService;
use App\Services\Jobs\DummyJobService;
use App\Services\Jobs\GeoOpJobService;
use App\Services\Wholesale\DummyWholesaleService;
use App\Services\Wholesale\ALMConnectService;

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

        // Wholesale Service (ALM Connect)
        $this->app->singleton(WholesaleServiceInterface::class, function ($app) {
            $driver = config('services.wholesale.driver', 'dummy');

            return match ($driver) {
                'alm' => new ALMConnectService(),
                default => new DummyWholesaleService(),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
