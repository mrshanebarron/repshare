<?php

namespace App\Console\Commands;

use App\Models\StockReservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessExpiredReservations extends Command
{
    protected $signature = 'reservations:process-expired';

    protected $description = 'Release expired stock reservations';

    public function handle(): int
    {
        $this->info('Processing expired reservations...');

        $expired = StockReservation::where('status', 'reserved')
            ->where('expires_at', '<', now())
            ->get();

        $this->info("Found {$expired->count()} expired reservations.");

        foreach ($expired as $reservation) {
            // Release the stock
            $stockLevel = $reservation->product->stockLevels()
                ->where('warehouse_id', $reservation->warehouse_id)
                ->first();

            if ($stockLevel) {
                $stockLevel->release($reservation->quantity);
            }

            // Update reservation status
            $reservation->update(['status' => 'released']);

            $this->info("  Released reservation {$reservation->id} for {$reservation->quantity} units.");
        }

        Log::info('Expired reservations processed', ['count' => $expired->count()]);

        return self::SUCCESS;
    }
}
