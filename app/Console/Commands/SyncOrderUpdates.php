<?php

namespace App\Console\Commands;

use App\Contracts\InventoryServiceInterface;
use App\Contracts\WholesaleServiceInterface;
use App\Services\Inventory\UnleashedInventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncOrderUpdates extends Command
{
    protected $signature = 'sync:orders {--source=all : Source to sync (all, unleashed, alm)}';

    protected $description = 'Sync order updates from external systems (Unleashed, ALM Connect)';

    public function handle(
        InventoryServiceInterface $inventoryService,
        WholesaleServiceInterface $wholesaleService
    ): int {
        $source = $this->option('source');
        $totalUpdated = 0;

        $this->info('Starting order sync...');

        // Sync from Unleashed
        if (in_array($source, ['all', 'unleashed'])) {
            $this->info('Syncing from Unleashed...');
            try {
                if ($inventoryService instanceof UnleashedInventoryService) {
                    $count = $inventoryService->syncSalesOrderUpdates();
                    $this->info("  Updated {$count} orders from Unleashed.");
                    $totalUpdated += $count;
                }
            } catch (\Exception $e) {
                $this->error('  Unleashed sync failed: ' . $e->getMessage());
                Log::error('Unleashed order sync failed', ['error' => $e->getMessage()]);
            }
        }

        // Sync from ALM Connect
        if (in_array($source, ['all', 'alm'])) {
            $this->info('Syncing from ALM Connect...');
            try {
                $count = $wholesaleService->syncOrderUpdates();
                $this->info("  Updated {$count} orders from ALM Connect.");
                $totalUpdated += $count;
            } catch (\Exception $e) {
                $this->error('  ALM Connect sync failed: ' . $e->getMessage());
                Log::error('ALM Connect order sync failed', ['error' => $e->getMessage()]);
            }
        }

        $this->info("Total orders updated: {$totalUpdated}");
        Log::info('Order sync completed', ['total_updated' => $totalUpdated]);

        return self::SUCCESS;
    }
}
