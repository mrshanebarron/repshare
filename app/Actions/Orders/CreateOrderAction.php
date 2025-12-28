<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Venue;
use App\Models\Producer;
use App\Models\Job;
use App\Enums\OrderStatus;
use App\Data\OrderLineData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    /**
     * Create a new master order from a collection of order lines.
     *
     * @param Collection<int, OrderLineData> $lines
     */
    public function execute(
        Venue $venue,
        Collection $lines,
        ?Producer $producer = null,
        ?Job $job = null,
        ?string $notes = null,
        ?string $deliveryDate = null
    ): Order {
        return DB::transaction(function () use ($venue, $lines, $producer, $job, $notes, $deliveryDate) {
            // Create the master order
            $order = Order::create([
                'venue_id' => $venue->id,
                'producer_id' => $producer?->id,
                'job_id' => $job?->id,
                'status' => OrderStatus::Draft,
                'notes' => $notes,
                'delivery_address' => $venue->address,
                'delivery_city' => $venue->city,
                'delivery_state' => $venue->state,
                'delivery_postcode' => $venue->postcode,
                'requested_delivery_date' => $deliveryDate,
            ]);

            // Add order lines
            foreach ($lines as $lineData) {
                $product = \App\Models\Product::where('sku', $lineData->sku)->first();

                if (!$product) {
                    throw new \Exception("Product not found: {$lineData->sku}");
                }

                OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'sku' => $lineData->sku,
                    'product_name' => $lineData->productName ?? $product->name,
                    'quantity' => $lineData->quantity,
                    'unit_price' => $lineData->unitPrice ?: $product->unit_price,
                    'discount_percent' => $lineData->discountPercent,
                    'notes' => $lineData->notes,
                ]);
            }

            // Calculate totals
            $order->recalculateTotals();

            return $order;
        });
    }
}
