<?php

namespace App\Services\Inventory;

use App\Contracts\InventoryServiceInterface;
use App\Data\ProductData;
use App\Data\WarehouseData;
use App\Data\StockLevelData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Real Unleashed API implementation.
 * Requires UNLEASHED_API_ID and UNLEASHED_API_KEY in .env
 */
class UnleashedInventoryService implements InventoryServiceInterface
{
    private string $apiId;
    private string $apiKey;
    private string $baseUrl = 'https://api.unleashedsoftware.com';

    public function __construct()
    {
        $this->apiId = config('services.unleashed.api_id', '');
        $this->apiKey = config('services.unleashed.api_key', '');
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $signature = $this->generateSignature($endpoint);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'api-auth-id' => $this->apiId,
            'api-auth-signature' => $signature,
        ])->{$method}($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            Log::error('Unleashed API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Unleashed API error: ' . $response->status());
        }

        return $response->json();
    }

    private function generateSignature(string $endpoint): string
    {
        $queryString = parse_url($endpoint, PHP_URL_QUERY) ?? '';
        return base64_encode(hash_hmac('sha256', $queryString, $this->apiKey, true));
    }

    public function getProducts(): Collection
    {
        return Cache::remember('unleashed.products', 300, function () {
            $products = collect();
            $page = 1;

            do {
                $response = $this->request('get', "/Products/$page");
                $items = $response['Items'] ?? [];

                foreach ($items as $item) {
                    $products->push(new ProductData(
                        sku: $item['ProductCode'],
                        name: $item['ProductDescription'],
                        description: $item['Notes'] ?? null,
                        brandId: $item['SupplierGuid'] ?? null,
                        brandName: $item['SupplierName'] ?? null,
                        category: $item['ProductGroup'] ?? null,
                        unitPrice: (float) ($item['DefaultSellPrice'] ?? 0),
                        packSize: (int) ($item['PackSize'] ?? 1),
                        caseSize: (int) ($item['InnerCartonQuantity'] ?? 1),
                        isActive: !($item['IsObsoleted'] ?? false),
                        externalId: $item['Guid'],
                    ));
                }

                $page++;
            } while (count($items) === 200);

            return $products;
        });
    }

    public function getProduct(string $sku): ?ProductData
    {
        $products = $this->getProducts();
        return $products->firstWhere('sku', $sku);
    }

    public function getWarehouses(): Collection
    {
        return Cache::remember('unleashed.warehouses', 300, function () {
            $response = $this->request('get', '/Warehouses');

            return collect($response['Items'] ?? [])->map(fn ($item) => new WarehouseData(
                id: $item['Guid'],
                name: $item['WarehouseName'],
                code: $item['WarehouseCode'],
                address: $item['AddressLine1'] ?? null,
                city: $item['City'] ?? null,
                state: $item['Region'] ?? null,
                postcode: $item['PostCode'] ?? null,
                country: $item['Country'] ?? null,
                isActive: !($item['IsObsoleted'] ?? false),
                externalId: $item['Guid'],
            ));
        });
    }

    public function getWarehouse(string $warehouseId): ?WarehouseData
    {
        $warehouses = $this->getWarehouses();
        return $warehouses->firstWhere('id', $warehouseId);
    }

    public function getStockOnHand(string $sku, string $warehouseId): int
    {
        $cacheKey = "unleashed.stock.{$sku}.{$warehouseId}";

        return Cache::remember($cacheKey, 60, function () use ($sku, $warehouseId) {
            $response = $this->request('get', "/StockOnHand?productCode={$sku}&warehouseCode={$warehouseId}");

            $item = collect($response['Items'] ?? [])->first();
            return (int) ($item['QtyOnHand'] ?? 0);
        });
    }

    public function getStockLevels(string $sku): Collection
    {
        $cacheKey = "unleashed.stock.{$sku}";

        return Cache::remember($cacheKey, 60, function () use ($sku) {
            $response = $this->request('get', "/StockOnHand?productCode={$sku}");

            return collect($response['Items'] ?? [])->map(fn ($item) => new StockLevelData(
                sku: $sku,
                warehouseId: $item['WarehouseCode'],
                warehouseName: $item['WarehouseName'] ?? null,
                quantityOnHand: (int) ($item['QtyOnHand'] ?? 0),
                quantityReserved: (int) ($item['AllocatedQty'] ?? 0),
                quantityAvailable: (int) ($item['AvailableQty'] ?? 0),
                quantityOnOrder: (int) ($item['QtyOnOrder'] ?? 0),
            ));
        });
    }

    public function hasStock(string $sku, string $warehouseId, int $quantity): bool
    {
        $levels = $this->getStockLevels($sku);
        $level = $levels->firstWhere('warehouseId', $warehouseId);

        return $level && $level->quantityAvailable >= $quantity;
    }

    public function reserveStock(string $sku, string $warehouseId, int $quantity, string $orderId): bool
    {
        // Unleashed doesn't have a reservation API - this would be handled via sales order creation
        // For now, we just verify stock is available
        return $this->hasStock($sku, $warehouseId, $quantity);
    }

    public function releaseStock(string $orderId): bool
    {
        // Cancel/void the sales order in Unleashed
        try {
            $this->request('post', "/SalesOrders/{$orderId}/Cancel");
            Log::info('Unleashed order cancelled', ['order_id' => $orderId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel Unleashed order', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function commitStock(string $orderId): bool
    {
        // Complete the sales order in Unleashed
        try {
            $this->request('post', "/SalesOrders/{$orderId}/Complete");
            Log::info('Unleashed order completed', ['order_id' => $orderId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to complete Unleashed order', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create a sales order in Unleashed for a brand order.
     */
    public function createSalesOrder(\App\Models\BrandOrder $brandOrder): ?string
    {
        $order = $brandOrder->order;
        $venue = $order->venue;
        $warehouse = $brandOrder->warehouse;

        // Build order lines
        $lines = $brandOrder->lines->map(function ($line, $index) use ($warehouse) {
            $product = $line->product;

            return [
                'LineNumber' => $index + 1,
                'ProductGuid' => $product->unleashed_guid,
                'ProductCode' => $line->sku,
                'ProductDescription' => $line->product_name,
                'OrderQuantity' => $line->quantity,
                'UnitPrice' => $line->unit_price,
                'DiscountRate' => $line->discount_percent,
                'LineTax' => $line->tax_amount,
                'LineTotal' => $line->line_total,
                'WarehouseCode' => $warehouse?->code,
                'WarehouseGuid' => $warehouse?->unleashed_guid,
            ];
        })->toArray();

        try {
            $response = $this->request('post', '/SalesOrders', [
                'OrderNumber' => $brandOrder->order_number,
                'OrderDate' => now()->toIso8601String(),
                'RequiredDate' => $order->requested_delivery_date?->toIso8601String(),
                'CustomerCode' => $venue->code ?? 'VENUE-' . $venue->id,
                'CustomerName' => $venue->name,
                'DeliveryName' => $venue->name,
                'DeliveryStreetAddress' => $order->delivery_address,
                'DeliveryCity' => $order->delivery_city,
                'DeliveryRegion' => $order->delivery_state,
                'DeliveryPostCode' => $order->delivery_postcode,
                'DeliveryCountry' => 'Australia',
                'Comments' => $order->notes,
                'WarehouseCode' => $warehouse?->code,
                'WarehouseGuid' => $warehouse?->unleashed_guid,
                'SalesOrderLines' => $lines,
                'SubTotal' => $brandOrder->subtotal,
                'TaxTotal' => $brandOrder->tax_total,
                'Total' => $brandOrder->grand_total,
                'OrderStatus' => 'Parked', // Will be completed when ready to ship
            ]);

            $unleashedOrderId = $response['Guid'] ?? null;

            if ($unleashedOrderId) {
                $brandOrder->update([
                    'unleashed_order_id' => $unleashedOrderId,
                ]);

                Log::info('Unleashed sales order created', [
                    'brand_order_id' => $brandOrder->id,
                    'unleashed_order_id' => $unleashedOrderId,
                ]);
            }

            return $unleashedOrderId;

        } catch (\Exception $e) {
            Log::error('Failed to create Unleashed sales order', [
                'brand_order_id' => $brandOrder->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get sales order status from Unleashed.
     */
    public function getSalesOrderStatus(string $orderId): ?string
    {
        try {
            $response = $this->request('get', "/SalesOrders/{$orderId}");
            return $response['OrderStatus'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sync sales order updates from Unleashed.
     */
    public function syncSalesOrderUpdates(): int
    {
        $updated = 0;
        $lastSync = Cache::get('unleashed.orders.last_sync', now()->subHour());

        try {
            $response = $this->request('get', '/SalesOrders?modifiedSince=' . $lastSync->format('Y-m-d\TH:i:s'));

            foreach ($response['Items'] ?? [] as $unleashedOrder) {
                $brandOrder = \App\Models\BrandOrder::where('unleashed_order_id', $unleashedOrder['Guid'])->first();

                if (!$brandOrder) {
                    continue;
                }

                // Map Unleashed status to internal status
                $statusMap = [
                    'Parked' => \App\Enums\OrderStatus::Pending,
                    'Placed' => \App\Enums\OrderStatus::Confirmed,
                    'Backordered' => \App\Enums\OrderStatus::Processing,
                    'Completed' => \App\Enums\OrderStatus::Shipped,
                    'Deleted' => \App\Enums\OrderStatus::Cancelled,
                ];

                if (isset($statusMap[$unleashedOrder['OrderStatus']])) {
                    $brandOrder->update([
                        'status' => $statusMap[$unleashedOrder['OrderStatus']],
                    ]);
                    $updated++;
                }
            }

            Cache::put('unleashed.orders.last_sync', now());

        } catch (\Exception $e) {
            Log::error('Unleashed sales order sync failed', ['error' => $e->getMessage()]);
        }

        return $updated;
    }

    public function syncProducts(): int
    {
        Cache::forget('unleashed.products');
        $products = $this->getProducts();

        // Sync to local database
        foreach ($products as $product) {
            \App\Models\Product::updateOrCreate(
                ['sku' => $product->sku],
                [
                    'name' => $product->name,
                    'description' => $product->description,
                    'category' => $product->category,
                    'unit_price' => $product->unitPrice,
                    'pack_size' => $product->packSize,
                    'case_size' => $product->caseSize,
                    'is_active' => $product->isActive,
                    'external_id' => $product->externalId,
                    'unleashed_guid' => $product->externalId,
                ]
            );
        }

        return $products->count();
    }

    public function syncWarehouses(): int
    {
        Cache::forget('unleashed.warehouses');
        $warehouses = $this->getWarehouses();

        foreach ($warehouses as $warehouse) {
            \App\Models\Warehouse::updateOrCreate(
                ['code' => $warehouse->code],
                [
                    'name' => $warehouse->name,
                    'address' => $warehouse->address,
                    'city' => $warehouse->city,
                    'state' => $warehouse->state,
                    'postcode' => $warehouse->postcode,
                    'country' => $warehouse->country,
                    'is_active' => $warehouse->isActive,
                    'external_id' => $warehouse->id,
                    'unleashed_guid' => $warehouse->id,
                ]
            );
        }

        return $warehouses->count();
    }

    public function getLastSyncTime(): ?\DateTimeInterface
    {
        $timestamp = Cache::get('unleashed.last_sync');
        return $timestamp ? \Carbon\Carbon::parse($timestamp) : null;
    }
}
