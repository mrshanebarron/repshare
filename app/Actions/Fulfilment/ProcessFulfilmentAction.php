<?php

namespace App\Actions\Fulfilment;

use App\Models\BrandOrder;
use App\Models\Carrier;
use App\Enums\FulfilmentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderDelivered;
use Illuminate\Support\Facades\Log;

class ProcessFulfilmentAction
{
    /**
     * Mark order as picked (items gathered from warehouse).
     */
    public function markPicked(BrandOrder $brandOrder): void
    {
        $brandOrder->update([
            'fulfilment_status' => FulfilmentStatus::Picking,
            'picked_at' => now(),
        ]);

        Log::info('Order marked as picked', ['brand_order_id' => $brandOrder->id]);
    }

    /**
     * Mark order as packed with packer details.
     */
    public function markPacked(
        BrandOrder $brandOrder,
        ?string $packerName = null,
        ?string $notes = null
    ): void {
        $brandOrder->update([
            'fulfilment_status' => FulfilmentStatus::Packing,
            'packed_at' => now(),
            'packer_name' => $packerName,
            'packing_notes' => $notes,
        ]);

        Log::info('Order marked as packed', [
            'brand_order_id' => $brandOrder->id,
            'packer' => $packerName,
        ]);
    }

    /**
     * Dispatch order with carrier and tracking information.
     */
    public function dispatch(
        BrandOrder $brandOrder,
        string $carrierCode,
        string $trackingNumber,
        ?string $carrierService = null,
        ?float $shippingCost = null
    ): void {
        $carrier = Carrier::where('code', $carrierCode)->first();

        $brandOrder->update([
            'fulfilment_status' => FulfilmentStatus::Dispatched,
            'status' => OrderStatus::Shipped,
            'carrier' => $carrier?->name ?? $carrierCode,
            'tracking_number' => $trackingNumber,
            'carrier_service' => $carrierService,
            'shipping_cost' => $shippingCost,
            'dispatched_at' => now(),
        ]);

        // Update master order status if all brand orders are shipped
        $this->updateMasterOrderStatus($brandOrder);

        Log::info('Order dispatched', [
            'brand_order_id' => $brandOrder->id,
            'carrier' => $carrierCode,
            'tracking' => $trackingNumber,
        ]);
    }

    /**
     * Mark order as delivered with optional proof.
     */
    public function markDelivered(
        BrandOrder $brandOrder,
        ?string $signatureName = null,
        ?string $deliveryProofUrl = null
    ): void {
        $brandOrder->update([
            'fulfilment_status' => FulfilmentStatus::Delivered,
            'status' => OrderStatus::Delivered,
            'delivered_at' => now(),
            'signature_name' => $signatureName,
            'delivery_proof' => $deliveryProofUrl,
        ]);

        // Update master order status
        $this->updateMasterOrderStatus($brandOrder);

        // Fire delivery event for notifications
        event(new OrderDelivered($brandOrder));

        Log::info('Order marked as delivered', [
            'brand_order_id' => $brandOrder->id,
            'signature' => $signatureName,
        ]);
    }

    /**
     * Report a delivery issue.
     */
    public function reportIssue(
        BrandOrder $brandOrder,
        string $issueType,
        string $description
    ): void {
        $brandOrder->update([
            'fulfilment_status' => FulfilmentStatus::Failed,
            'metadata' => array_merge($brandOrder->metadata ?? [], [
                'delivery_issue' => [
                    'type' => $issueType,
                    'description' => $description,
                    'reported_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        Log::warning('Delivery issue reported', [
            'brand_order_id' => $brandOrder->id,
            'issue_type' => $issueType,
        ]);
    }

    /**
     * Get tracking URL for order.
     */
    public function getTrackingUrl(BrandOrder $brandOrder): ?string
    {
        if (!$brandOrder->tracking_number || !$brandOrder->carrier) {
            return null;
        }

        $carrier = Carrier::where('name', $brandOrder->carrier)
            ->orWhere('code', $brandOrder->carrier)
            ->first();

        return $carrier?->getTrackingUrl($brandOrder->tracking_number);
    }

    /**
     * Update master order status based on all brand orders.
     */
    private function updateMasterOrderStatus(BrandOrder $brandOrder): void
    {
        $order = $brandOrder->order;
        $brandOrders = $order->brandOrders;

        // Check if all brand orders are delivered
        $allDelivered = $brandOrders->every(fn ($bo) => $bo->fulfilment_status === FulfilmentStatus::Delivered);

        if ($allDelivered) {
            $order->update([
                'status' => OrderStatus::Delivered,
                'completed_at' => now(),
            ]);
            return;
        }

        // Check if all brand orders are shipped
        $allShipped = $brandOrders->every(fn ($bo) => in_array($bo->fulfilment_status, [
            FulfilmentStatus::Dispatched,
            FulfilmentStatus::Delivered,
        ]));

        if ($allShipped) {
            $order->update(['status' => OrderStatus::Shipped]);
            return;
        }

        // Check if any brand order is in progress
        $anyInProgress = $brandOrders->contains(fn ($bo) => in_array($bo->fulfilment_status, [
            FulfilmentStatus::Picking,
            FulfilmentStatus::Packing,
        ]));

        if ($anyInProgress) {
            $order->update(['status' => OrderStatus::Processing]);
        }
    }
}
