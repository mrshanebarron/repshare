<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\BrandOrder;
use App\Models\OrderLine;
use App\Models\Brand;
use App\Models\Warehouse;
use App\Models\StockLevel;
use App\Models\StockReservation;
use App\Enums\OrderStatus;
use App\Enums\FulfilmentStatus;
use App\Contracts\InventoryServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Splits a master order into separate brand orders.
 * This is the core multi-vendor marketplace logic.
 *
 * Flow:
 * 1. Group order lines by brand
 * 2. For each brand, determine the optimal warehouse
 * 3. Create a BrandOrder for each brand
 * 4. Reserve stock for each line
 * 5. Calculate fees and commissions
 */
class SplitOrderByBrandAction
{
    public function __construct(
        private InventoryServiceInterface $inventoryService
    ) {}

    /**
     * @return Collection<int, BrandOrder>
     */
    public function execute(Order $order): Collection
    {
        return DB::transaction(function () use ($order) {
            $brandOrders = collect();

            // Group lines by brand
            $linesByBrand = $order->lines->groupBy(function ($line) {
                return $line->product->brand_id;
            });

            foreach ($linesByBrand as $brandId => $lines) {
                $brand = Brand::findOrFail($brandId);

                // Find optimal warehouse for this brand's products
                $warehouse = $this->findOptimalWarehouse($lines);

                // Create brand order
                $brandOrder = BrandOrder::create([
                    'order_id' => $order->id,
                    'brand_id' => $brandId,
                    'warehouse_id' => $warehouse?->id,
                    'three_pl_id' => $warehouse?->three_pl_id,
                    'status' => OrderStatus::Pending,
                    'fulfilment_status' => FulfilmentStatus::Pending,
                ]);

                // Assign lines to brand order and reserve stock
                foreach ($lines as $line) {
                    $line->update(['brand_order_id' => $brandOrder->id]);

                    // Reserve stock if warehouse found
                    if ($warehouse) {
                        $this->reserveStock($order, $brandOrder, $line, $warehouse);
                    }
                }

                // Calculate totals with fees
                $this->calculateBrandOrderTotals($brandOrder, $brand);

                $brandOrders->push($brandOrder->fresh());
            }

            // Update master order status
            $order->update(['status' => OrderStatus::Pending]);

            Log::info('Order split completed', [
                'order_id' => $order->id,
                'brand_orders' => $brandOrders->pluck('id'),
            ]);

            return $brandOrders;
        });
    }

    /**
     * Find the optimal warehouse that has stock for all lines.
     * If no single warehouse has all items, pick the one with most availability.
     */
    private function findOptimalWarehouse(Collection $lines): ?Warehouse
    {
        $warehouses = Warehouse::where('is_active', true)->get();

        $warehouseScores = [];

        foreach ($warehouses as $warehouse) {
            $score = 0;
            $canFulfillAll = true;

            foreach ($lines as $line) {
                $stockLevel = StockLevel::where('product_id', $line->product_id)
                    ->where('warehouse_id', $warehouse->id)
                    ->first();

                if ($stockLevel && $stockLevel->quantity_available >= $line->quantity) {
                    $score += $line->quantity;
                } else {
                    $canFulfillAll = false;
                }
            }

            $warehouseScores[$warehouse->id] = [
                'warehouse' => $warehouse,
                'score' => $score,
                'can_fulfill_all' => $canFulfillAll,
            ];
        }

        // Prefer warehouse that can fulfill all, otherwise pick highest score
        $sorted = collect($warehouseScores)->sortByDesc(function ($item) {
            return [$item['can_fulfill_all'], $item['score']];
        });

        $best = $sorted->first();

        return $best ? $best['warehouse'] : null;
    }

    private function reserveStock(Order $order, BrandOrder $brandOrder, OrderLine $line, Warehouse $warehouse): void
    {
        $stockLevel = StockLevel::where('product_id', $line->product_id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        if (!$stockLevel || !$stockLevel->hasStock($line->quantity)) {
            Log::warning('Insufficient stock for reservation', [
                'product_id' => $line->product_id,
                'warehouse_id' => $warehouse->id,
                'requested' => $line->quantity,
                'available' => $stockLevel?->quantity_available ?? 0,
            ]);
            return;
        }

        // Create reservation
        StockReservation::create([
            'order_id' => $order->id,
            'brand_order_id' => $brandOrder->id,
            'product_id' => $line->product_id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $line->quantity,
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(config('services.platform.stock_reservation_minutes', 30)),
        ]);

        // Update stock level
        $stockLevel->reserve($line->quantity);
    }

    private function calculateBrandOrderTotals(BrandOrder $brandOrder, Brand $brand): void
    {
        $lines = $brandOrder->lines;

        $subtotal = $lines->sum('line_total');
        $discountTotal = $lines->sum('discount_amount');
        $taxTotal = $lines->sum('tax_amount');

        // Calculate fees
        $commissionRate = $brand->commission_rate ?: 0;
        $platformFeeRate = $brand->platform_fee_percent ?: config('services.platform.default_fee_percent', 5);

        $commissionAmount = $subtotal * ($commissionRate / 100);
        $platformFee = $subtotal * ($platformFeeRate / 100);

        $grandTotal = $subtotal + $taxTotal - $discountTotal;
        $netToBrand = $grandTotal - $commissionAmount - $platformFee;

        $brandOrder->update([
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'commission_amount' => $commissionAmount,
            'platform_fee' => $platformFee,
            'grand_total' => $grandTotal,
            'net_to_brand' => $netToBrand,
        ]);
    }
}
