<?php

namespace App\Services\Wholesale;

use App\Contracts\WholesaleServiceInterface;
use App\Data\WholesaleOrderData;
use App\Models\BrandOrder;
use App\Enums\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Dummy wholesale service for development/testing.
 */
class DummyWholesaleService implements WholesaleServiceInterface
{
    public function submitOrder(BrandOrder $brandOrder): WholesaleOrderData
    {
        $externalId = 'ALM-' . Str::upper(Str::random(8));

        $brandOrder->update([
            'alm_order_id' => $externalId,
            'alm_status' => 'submitted',
            'alm_submitted_at' => now(),
        ]);

        return new WholesaleOrderData(
            externalId: $externalId,
            localId: $brandOrder->id,
            status: 'submitted',
            customerReference: "RS-{$brandOrder->id}",
            totalAmount: $brandOrder->grand_total,
            submittedAt: now(),
        );
    }

    public function getOrderStatus(string $externalId): ?string
    {
        $brandOrder = BrandOrder::where('alm_order_id', $externalId)->first();
        return $brandOrder?->alm_status ?? 'unknown';
    }

    public function cancelOrder(string $externalId, string $reason): bool
    {
        $brandOrder = BrandOrder::where('alm_order_id', $externalId)->first();

        if ($brandOrder) {
            $brandOrder->update([
                'alm_status' => 'cancelled',
                'status' => OrderStatus::Cancelled,
            ]);
            return true;
        }

        return false;
    }

    public function getProducts(): Collection
    {
        return collect([
            ['sku' => 'WINE-001', 'name' => 'Sample Red Wine', 'supplier_id' => 'SUP001', 'price' => 25.00, 'available' => true],
            ['sku' => 'WINE-002', 'name' => 'Sample White Wine', 'supplier_id' => 'SUP001', 'price' => 22.00, 'available' => true],
            ['sku' => 'BEER-001', 'name' => 'Sample Craft Beer', 'supplier_id' => 'SUP002', 'price' => 8.50, 'available' => true],
            ['sku' => 'SPIRIT-001', 'name' => 'Sample Gin', 'supplier_id' => 'SUP003', 'price' => 45.00, 'available' => true],
        ]);
    }

    public function checkAvailability(string $sku, int $quantity): bool
    {
        return true; // Always available in dummy mode
    }

    public function getPrice(string $sku, int $quantity): ?float
    {
        $products = $this->getProducts();
        $product = $products->firstWhere('sku', $sku);
        return $product['price'] ?? null;
    }

    public function syncOrderUpdates(): int
    {
        // Simulate random order updates
        $pendingOrders = BrandOrder::whereNotNull('alm_order_id')
            ->where('alm_status', 'submitted')
            ->inRandomOrder()
            ->take(3)
            ->get();

        foreach ($pendingOrders as $order) {
            $order->update([
                'alm_status' => 'confirmed',
                'status' => OrderStatus::Confirmed,
            ]);
        }

        return $pendingOrders->count();
    }
}
