<?php

namespace App\Contracts;

use App\Data\ProductData;
use App\Data\WarehouseData;
use App\Data\StockLevelData;
use Illuminate\Support\Collection;

interface InventoryServiceInterface
{
    /**
     * Get all products from the inventory system
     * @return Collection<int, ProductData>
     */
    public function getProducts(): Collection;

    /**
     * Get a single product by SKU
     */
    public function getProduct(string $sku): ?ProductData;

    /**
     * Get all warehouses
     * @return Collection<int, WarehouseData>
     */
    public function getWarehouses(): Collection;

    /**
     * Get a single warehouse by ID
     */
    public function getWarehouse(string $warehouseId): ?WarehouseData;

    /**
     * Get stock on hand for a product at a specific warehouse
     */
    public function getStockOnHand(string $sku, string $warehouseId): int;

    /**
     * Get stock levels across all warehouses for a product
     * @return Collection<int, StockLevelData>
     */
    public function getStockLevels(string $sku): Collection;

    /**
     * Check if sufficient stock is available
     */
    public function hasStock(string $sku, string $warehouseId, int $quantity): bool;

    /**
     * Reserve stock for an order (temporary hold)
     */
    public function reserveStock(string $sku, string $warehouseId, int $quantity, string $orderId): bool;

    /**
     * Release reserved stock
     */
    public function releaseStock(string $orderId): bool;

    /**
     * Commit reserved stock (convert to sales order)
     */
    public function commitStock(string $orderId): bool;

    /**
     * Sync all products from external system
     */
    public function syncProducts(): int;

    /**
     * Sync all warehouses from external system
     */
    public function syncWarehouses(): int;

    /**
     * Get last sync timestamp
     */
    public function getLastSyncTime(): ?\DateTimeInterface;
}
