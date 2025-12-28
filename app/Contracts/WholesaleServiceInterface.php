<?php

namespace App\Contracts;

use App\Data\WholesaleOrderData;
use App\Data\WholesaleOrderLineData;
use App\Models\BrandOrder;
use Illuminate\Support\Collection;

interface WholesaleServiceInterface
{
    /**
     * Submit a brand order to the wholesale system.
     */
    public function submitOrder(BrandOrder $brandOrder): WholesaleOrderData;

    /**
     * Get order status from wholesale system.
     */
    public function getOrderStatus(string $externalId): ?string;

    /**
     * Cancel an order in the wholesale system.
     */
    public function cancelOrder(string $externalId, string $reason): bool;

    /**
     * Get available products from the wholesale catalog.
     */
    public function getProducts(): Collection;

    /**
     * Check if a product is available for ordering.
     */
    public function checkAvailability(string $sku, int $quantity): bool;

    /**
     * Get pricing for a product (may differ from standard).
     */
    public function getPrice(string $sku, int $quantity): ?float;

    /**
     * Sync orders that have been updated in the wholesale system.
     */
    public function syncOrderUpdates(): int;
}
