<?php

namespace App\Services\Inventory;

use App\Contracts\InventoryServiceInterface;
use App\Data\ProductData;
use App\Data\WarehouseData;
use App\Data\StockLevelData;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\StockLevel;
use Illuminate\Support\Collection;

/**
 * Dummy implementation of InventoryService that uses local database.
 * Replace with UnleashedInventoryService when API credentials are available.
 */
class DummyInventoryService implements InventoryServiceInterface
{
    public function getProducts(): Collection
    {
        return Product::with('brand')
            ->where('is_active', true)
            ->get()
            ->map(fn (Product $p) => new ProductData(
                sku: $p->sku,
                name: $p->name,
                description: $p->description,
                brandId: (string) $p->brand_id,
                brandName: $p->brand?->name,
                category: $p->category,
                subcategory: $p->subcategory,
                unitPrice: (float) $p->unit_price,
                packSize: $p->pack_size,
                caseSize: $p->case_size,
                imageUrl: $p->image_url,
                alcoholPercent: $p->alcohol_percent ? (float) $p->alcohol_percent : null,
                countryOfOrigin: $p->country_of_origin,
                region: $p->region,
                isActive: $p->is_active,
                externalId: $p->external_id,
            ));
    }

    public function getProduct(string $sku): ?ProductData
    {
        $product = Product::with('brand')->where('sku', $sku)->first();

        if (!$product) {
            return null;
        }

        return new ProductData(
            sku: $product->sku,
            name: $product->name,
            description: $product->description,
            brandId: (string) $product->brand_id,
            brandName: $product->brand?->name,
            category: $product->category,
            subcategory: $product->subcategory,
            unitPrice: (float) $product->unit_price,
            packSize: $product->pack_size,
            caseSize: $product->case_size,
            imageUrl: $product->image_url,
            alcoholPercent: $product->alcohol_percent ? (float) $product->alcohol_percent : null,
            countryOfOrigin: $product->country_of_origin,
            region: $product->region,
            isActive: $product->is_active,
            externalId: $product->external_id,
        );
    }

    public function getWarehouses(): Collection
    {
        return Warehouse::with('threePL')
            ->where('is_active', true)
            ->get()
            ->map(fn (Warehouse $w) => new WarehouseData(
                id: (string) $w->id,
                name: $w->name,
                code: $w->code,
                address: $w->address,
                city: $w->city,
                state: $w->state,
                postcode: $w->postcode,
                country: $w->country,
                latitude: $w->latitude ? (float) $w->latitude : null,
                longitude: $w->longitude ? (float) $w->longitude : null,
                threePlId: $w->three_pl_id ? (string) $w->three_pl_id : null,
                threePlName: $w->threePL?->name,
                isActive: $w->is_active,
                externalId: $w->external_id,
            ));
    }

    public function getWarehouse(string $warehouseId): ?WarehouseData
    {
        $warehouse = Warehouse::with('threePL')->find($warehouseId);

        if (!$warehouse) {
            return null;
        }

        return new WarehouseData(
            id: (string) $warehouse->id,
            name: $warehouse->name,
            code: $warehouse->code,
            address: $warehouse->address,
            city: $warehouse->city,
            state: $warehouse->state,
            postcode: $warehouse->postcode,
            country: $warehouse->country,
            latitude: $warehouse->latitude ? (float) $warehouse->latitude : null,
            longitude: $warehouse->longitude ? (float) $warehouse->longitude : null,
            threePlId: $warehouse->three_pl_id ? (string) $warehouse->three_pl_id : null,
            threePlName: $warehouse->threePL?->name,
            isActive: $warehouse->is_active,
            externalId: $warehouse->external_id,
        );
    }

    public function getStockOnHand(string $sku, string $warehouseId): int
    {
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            return 0;
        }

        $stock = StockLevel::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $stock?->quantity_on_hand ?? 0;
    }

    public function getStockLevels(string $sku): Collection
    {
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            return collect();
        }

        return StockLevel::where('product_id', $product->id)
            ->with('warehouse')
            ->get()
            ->map(fn (StockLevel $s) => new StockLevelData(
                sku: $sku,
                warehouseId: (string) $s->warehouse_id,
                warehouseName: $s->warehouse?->name,
                quantityOnHand: $s->quantity_on_hand,
                quantityReserved: $s->quantity_reserved,
                quantityAvailable: $s->quantity_available,
                quantityOnOrder: $s->quantity_on_order,
                lastUpdated: $s->last_synced_at?->toIso8601String(),
            ));
    }

    public function hasStock(string $sku, string $warehouseId, int $quantity): bool
    {
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            return false;
        }

        $stock = StockLevel::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $stock && $stock->quantity_available >= $quantity;
    }

    public function reserveStock(string $sku, string $warehouseId, int $quantity, string $orderId): bool
    {
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            return false;
        }

        $stock = StockLevel::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$stock || !$stock->hasStock($quantity)) {
            return false;
        }

        return $stock->reserve($quantity);
    }

    public function releaseStock(string $orderId): bool
    {
        $reservations = \App\Models\StockReservation::where('order_id', $orderId)
            ->where('status', 'reserved')
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->release();
        }

        return true;
    }

    public function commitStock(string $orderId): bool
    {
        $reservations = \App\Models\StockReservation::where('order_id', $orderId)
            ->where('status', 'reserved')
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->commit();
        }

        return true;
    }

    public function syncProducts(): int
    {
        // Dummy implementation - products are already in local DB
        return Product::count();
    }

    public function syncWarehouses(): int
    {
        // Dummy implementation - warehouses are already in local DB
        return Warehouse::count();
    }

    public function getLastSyncTime(): ?\DateTimeInterface
    {
        return now();
    }
}
