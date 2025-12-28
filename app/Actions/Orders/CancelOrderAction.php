<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\FulfilmentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cancels an order and releases all stock reservations.
 */
class CancelOrderAction
{
    public function execute(Order $order, string $reason = ''): Order
    {
        if ($order->status->isFinal()) {
            throw new \Exception('Cannot cancel a completed order');
        }

        return DB::transaction(function () use ($order, $reason) {
            // Release all stock reservations
            foreach ($order->stockReservations()->where('status', 'reserved')->get() as $reservation) {
                $reservation->release();
            }

            // Cancel brand orders
            foreach ($order->brandOrders as $brandOrder) {
                $brandOrder->update([
                    'status' => OrderStatus::Cancelled,
                    'fulfilment_status' => FulfilmentStatus::Failed,
                ]);
            }

            // Cancel master order
            $order->update([
                'status' => OrderStatus::Cancelled,
                'notes' => $order->notes . "\n\nCancellation reason: " . $reason,
            ]);

            Log::info('Order cancelled', [
                'order_id' => $order->id,
                'reason' => $reason,
            ]);

            return $order->fresh();
        });
    }
}
