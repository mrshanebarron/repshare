<?php

namespace App\Services\Wholesale;

use App\Contracts\WholesaleServiceInterface;
use App\Data\WholesaleOrderData;
use App\Models\BrandOrder;
use App\Enums\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ALM Connect integration for wholesale ordering.
 * ALM Connect is the commercial order routing system.
 */
class ALMConnectService implements WholesaleServiceInterface
{
    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;
    private string $accountId;

    public function __construct()
    {
        $this->apiKey = config('services.alm_connect.api_key', '');
        $this->apiSecret = config('services.alm_connect.api_secret', '');
        $this->baseUrl = config('services.alm_connect.base_url', 'https://api.almconnect.com.au/v1');
        $this->accountId = config('services.alm_connect.account_id', '');
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $timestamp = now()->timestamp;
        $signature = $this->generateSignature($method, $endpoint, $timestamp);

        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->retry(3, 1000, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            })
            ->{$method}($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            Log::error('ALM Connect API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('ALM Connect API error: ' . $response->status() . ' - ' . $response->body());
        }

        return $response->json();
    }

    private function generateSignature(string $method, string $endpoint, int $timestamp): string
    {
        $payload = strtoupper($method) . $endpoint . $timestamp . $this->apiKey;
        return hash_hmac('sha256', $payload, $this->apiSecret);
    }

    public function submitOrder(BrandOrder $brandOrder): WholesaleOrderData
    {
        $order = $brandOrder->order;
        $venue = $order->venue;

        // Build order lines
        $lines = $brandOrder->lines->map(fn ($line) => [
            'sku' => $line->sku,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price,
            'description' => $line->product_name,
        ])->toArray();

        $response = $this->request('post', '/orders', [
            'account_id' => $this->accountId,
            'customer_reference' => "RS-{$brandOrder->id}",
            'supplier_id' => $brandOrder->brand->alm_supplier_id,
            'delivery' => [
                'name' => $venue->name,
                'address_1' => $order->delivery_address,
                'city' => $order->delivery_city,
                'state' => $order->delivery_state,
                'postcode' => $order->delivery_postcode,
                'country' => 'AU',
                'contact_name' => $venue->contact_name,
                'contact_phone' => $venue->phone,
                'contact_email' => $venue->email,
                'instructions' => $order->notes,
            ],
            'lines' => $lines,
            'requested_delivery_date' => $order->requested_delivery_date?->format('Y-m-d'),
        ]);

        // Update brand order with ALM reference
        $brandOrder->update([
            'alm_order_id' => $response['order_id'],
            'alm_status' => $response['status'] ?? 'submitted',
            'alm_submitted_at' => now(),
        ]);

        Log::info('Order submitted to ALM Connect', [
            'brand_order_id' => $brandOrder->id,
            'alm_order_id' => $response['order_id'],
        ]);

        return new WholesaleOrderData(
            externalId: $response['order_id'],
            localId: $brandOrder->id,
            status: $response['status'] ?? 'submitted',
            customerReference: "RS-{$brandOrder->id}",
            totalAmount: $response['total'] ?? null,
            submittedAt: now(),
        );
    }

    public function getOrderStatus(string $externalId): ?string
    {
        try {
            $response = $this->request('get', "/orders/{$externalId}");
            return $response['status'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to get ALM order status', [
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function cancelOrder(string $externalId, string $reason): bool
    {
        try {
            $this->request('post', "/orders/{$externalId}/cancel", [
                'reason' => $reason,
            ]);

            // Update local record
            BrandOrder::where('alm_order_id', $externalId)->update([
                'alm_status' => 'cancelled',
                'status' => OrderStatus::Cancelled,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel ALM order', [
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getProducts(): Collection
    {
        return Cache::remember('alm.products', 300, function () {
            $products = collect();
            $page = 1;

            do {
                $response = $this->request('get', "/products?page={$page}&per_page=100");
                $items = $response['data'] ?? [];

                foreach ($items as $item) {
                    $products->push([
                        'sku' => $item['sku'],
                        'name' => $item['name'],
                        'supplier_id' => $item['supplier_id'],
                        'price' => $item['price'],
                        'available' => $item['available'] ?? true,
                    ]);
                }

                $page++;
                $hasMore = ($response['meta']['current_page'] ?? 0) < ($response['meta']['last_page'] ?? 0);
            } while ($hasMore);

            return $products;
        });
    }

    public function checkAvailability(string $sku, int $quantity): bool
    {
        try {
            $response = $this->request('get', "/products/{$sku}/availability?quantity={$quantity}");
            return $response['available'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPrice(string $sku, int $quantity): ?float
    {
        try {
            $response = $this->request('get', "/products/{$sku}/price?quantity={$quantity}");
            return $response['unit_price'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function syncOrderUpdates(): int
    {
        $updated = 0;
        $lastSync = Cache::get('alm.last_sync', now()->subHour());

        try {
            $response = $this->request('get', '/orders/updates?since=' . $lastSync->toIso8601String());

            foreach ($response['data'] ?? [] as $orderUpdate) {
                $brandOrder = BrandOrder::where('alm_order_id', $orderUpdate['order_id'])->first();

                if (!$brandOrder) {
                    continue;
                }

                $brandOrder->update([
                    'alm_status' => $orderUpdate['status'],
                    'alm_tracking_number' => $orderUpdate['tracking_number'] ?? null,
                    'alm_carrier' => $orderUpdate['carrier'] ?? null,
                    'alm_shipped_at' => isset($orderUpdate['shipped_at']) ? Carbon::parse($orderUpdate['shipped_at']) : null,
                ]);

                // Map ALM status to internal status
                $this->mapStatusToInternal($brandOrder, $orderUpdate['status']);

                $updated++;
            }

            Cache::put('alm.last_sync', now());

        } catch (\Exception $e) {
            Log::error('ALM sync failed', ['error' => $e->getMessage()]);
        }

        return $updated;
    }

    private function mapStatusToInternal(BrandOrder $brandOrder, string $almStatus): void
    {
        $statusMap = [
            'submitted' => OrderStatus::Pending,
            'confirmed' => OrderStatus::Confirmed,
            'processing' => OrderStatus::Processing,
            'shipped' => OrderStatus::Shipped,
            'delivered' => OrderStatus::Delivered,
            'cancelled' => OrderStatus::Cancelled,
            'failed' => OrderStatus::Failed,
        ];

        if (isset($statusMap[$almStatus])) {
            $brandOrder->update(['status' => $statusMap[$almStatus]]);
        }
    }
}
