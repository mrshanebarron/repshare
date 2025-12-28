<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\BrandOrder;
use App\Enums\OrderStatus;
use App\Enums\FulfilmentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Confirms an order and all its brand orders.
 * This commits the stock reservations and notifies relevant parties.
 */
class ConfirmOrderAction
{
    public function execute(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            // Commit all stock reservations
            foreach ($order->stockReservations()->where('status', 'reserved')->get() as $reservation) {
                $reservation->commit();
            }

            // Update brand orders
            foreach ($order->brandOrders as $brandOrder) {
                $brandOrder->update([
                    'status' => OrderStatus::Confirmed,
                    'fulfilment_status' => FulfilmentStatus::Assigned,
                ]);

                // Here we would send to external systems:
                // - Unleashed sales order
                // - ALM Connect order
                // - 3PL notification

                Log::info('Brand order confirmed', [
                    'brand_order_id' => $brandOrder->id,
                    'brand_id' => $brandOrder->brand_id,
                    'warehouse_id' => $brandOrder->warehouse_id,
                ]);
            }

            // Update master order
            $order->update([
                'status' => OrderStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            Log::info('Order confirmed', ['order_id' => $order->id]);

            return $order->fresh();
        });
    }
}
