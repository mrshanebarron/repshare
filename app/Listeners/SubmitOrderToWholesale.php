<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Contracts\WholesaleServiceInterface;
use App\Contracts\InventoryServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SubmitOrderToWholesale implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private WholesaleServiceInterface $wholesaleService,
        private InventoryServiceInterface $inventoryService,
    ) {}

    public function handle(OrderConfirmed $event): void
    {
        $order = $event->order;

        Log::info('Submitting order to wholesale systems', ['order_id' => $order->id]);

        foreach ($order->brandOrders as $brandOrder) {
            try {
                // Submit to ALM Connect
                $this->wholesaleService->submitOrder($brandOrder);

                // Commit stock in Unleashed
                if ($brandOrder->unleashed_order_id) {
                    $this->inventoryService->commitStock($brandOrder->unleashed_order_id);
                }

                Log::info('Brand order submitted to wholesale', [
                    'brand_order_id' => $brandOrder->id,
                    'alm_order_id' => $brandOrder->alm_order_id,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to submit brand order to wholesale', [
                    'brand_order_id' => $brandOrder->id,
                    'error' => $e->getMessage(),
                ]);

                // Mark order as failed but don't throw - let other brand orders process
                $brandOrder->update([
                    'status' => \App\Enums\OrderStatus::Failed,
                    'metadata' => array_merge($brandOrder->metadata ?? [], [
                        'wholesale_error' => $e->getMessage(),
                        'failed_at' => now()->toIso8601String(),
                    ]),
                ]);
            }
        }
    }
}
