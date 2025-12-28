<?php

namespace App\Console\Commands;

use App\Contracts\InventoryServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncUnleashedWarehouses extends Command
{
    protected $signature = 'sync:unleashed-warehouses';

    protected $description = 'Sync warehouses from Unleashed inventory system';

    public function handle(InventoryServiceInterface $inventoryService): int
    {
        $this->info('Starting Unleashed warehouse sync...');

        try {
            $count = $inventoryService->syncWarehouses();
            $this->info("Synced {$count} warehouses from Unleashed.");

            Log::info('Unleashed warehouse sync completed', ['count' => $count]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('Unleashed warehouse sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}
