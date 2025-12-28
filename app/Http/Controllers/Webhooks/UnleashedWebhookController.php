<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\BrandOrder;
use App\Enums\OrderStatus;
use App\Enums\FulfilmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UnleashedWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->all();
        $event = $payload['WebhookEvent'] ?? null;
        $data = $payload['Data'] ?? $payload;

        Log::info('Unleashed webhook received', [
            'event' => $event,
            'guid' => $data['Guid'] ?? null,
        ]);

        // Verify webhook
        if (!$this->verifyWebhook($request)) {
            Log::warning('Unleashed webhook verification failed');
            return response('Unauthorized', 401);
        }

        return match ($event) {
            'SalesOrder.Created' => $this->handleSalesOrderCreated($data),
            'SalesOrder.Updated' => $this->handleSalesOrderUpdated($data),
            'SalesOrder.Completed' => $this->handleSalesOrderCompleted($data),
            'SalesOrder.Deleted' => $this->handleSalesOrderDeleted($data),
            'Product.Updated' => $this->handleProductUpdated($data),
            'StockOnHand.Updated' => $this->handleStockUpdated($data),
            default => response('Event not handled', 200),
        };
    }

    private function verifyWebhook(Request $request): bool
    {
        // Unleashed uses API key verification
        $apiId = $request->header('api-auth-id');
        $expectedApiId = config('services.unleashed.api_id');

        if (!$apiId || !$expectedApiId) {
            return true; // Skip verification if not configured
        }

        return $apiId === $expectedApiId;
    }

    private function handleSalesOrderCreated(array $data): Response
    {
        // Orders created from RepShare are already tracked
        // This handles orders created directly in Unleashed
        Log::info('Sales order created in Unleashed', ['guid' => $data['Guid'] ?? null]);

        return response('OK', 200);
    }

    private function handleSalesOrderUpdated(array $data): Response
    {
        $guid = $data['Guid'] ?? null;
        $brandOrder = BrandOrder::where('unleashed_order_id', $guid)->first();

        if (!$brandOrder) {
            return response('Order not found', 200);
        }

        $statusMap = [
            'Parked' => OrderStatus::Pending,
            'Placed' => OrderStatus::Confirmed,
            'Backordered' => OrderStatus::Processing,
            'Completed' => OrderStatus::Delivered,
            'Deleted' => OrderStatus::Cancelled,
        ];

        $status = $data['OrderStatus'] ?? null;
        if ($status && isset($statusMap[$status])) {
            $brandOrder->update(['status' => $statusMap[$status]]);
        }

        Log::info('Brand order updated from Unleashed', [
            'brand_order_id' => $brandOrder->id,
            'status' => $status,
        ]);

        return response('OK', 200);
    }

    private function handleSalesOrderCompleted(array $data): Response
    {
        $guid = $data['Guid'] ?? null;
        $brandOrder = BrandOrder::where('unleashed_order_id', $guid)->first();

        if (!$brandOrder) {
            return response('Order not found', 200);
        }

        $brandOrder->update([
            'status' => OrderStatus::Delivered,
            'fulfilment_status' => FulfilmentStatus::Dispatched,
        ]);

        Log::info('Brand order completed from Unleashed', ['brand_order_id' => $brandOrder->id]);

        return response('OK', 200);
    }

    private function handleSalesOrderDeleted(array $data): Response
    {
        $guid = $data['Guid'] ?? null;
        $brandOrder = BrandOrder::where('unleashed_order_id', $guid)->first();

        if (!$brandOrder) {
            return response('Order not found', 200);
        }

        $brandOrder->update(['status' => OrderStatus::Cancelled]);

        Log::info('Brand order cancelled from Unleashed', ['brand_order_id' => $brandOrder->id]);

        return response('OK', 200);
    }

    private function handleProductUpdated(array $data): Response
    {
        // Invalidate product cache
        Cache::forget('unleashed.products');

        $sku = $data['ProductCode'] ?? null;
        if ($sku) {
            Cache::forget("unleashed.stock.{$sku}");
        }

        Log::info('Product updated in Unleashed', ['sku' => $sku]);

        return response('OK', 200);
    }

    private function handleStockUpdated(array $data): Response
    {
        $sku = $data['ProductCode'] ?? null;
        $warehouseCode = $data['WarehouseCode'] ?? null;

        // Invalidate stock cache
        if ($sku) {
            Cache::forget("unleashed.stock.{$sku}");
            if ($warehouseCode) {
                Cache::forget("unleashed.stock.{$sku}.{$warehouseCode}");
            }
        }

        Log::info('Stock updated in Unleashed', [
            'sku' => $sku,
            'warehouse' => $warehouseCode,
        ]);

        return response('OK', 200);
    }
}
