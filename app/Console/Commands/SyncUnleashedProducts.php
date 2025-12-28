<?php

namespace App\Console\Commands;

use App\Contracts\InventoryServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncUnleashedProducts extends Command
{
    protected $signature = 'sync:unleashed-products {--force : Force sync even if recently synced}';

    protected $description = 'Sync products from Unleashed inventory system';

    public function handle(InventoryServiceInterface $inventoryService): int
    {
        $this->info('Starting Unleashed product sync...');

        try {
            $count = $inventoryService->syncProducts();
            $this->info("Synced {$count} products from Unleashed.");

            Log::info('Unleashed product sync completed', ['count' => $count]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('Unleashed product sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}
