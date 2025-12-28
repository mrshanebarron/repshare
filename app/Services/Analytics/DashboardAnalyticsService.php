<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\BrandOrder;
use App\Models\Job;
use App\Models\BillingRecord;
use App\Models\Product;
use App\Models\StockLevel;
use App\Contracts\InventoryServiceInterface;
use App\Contracts\JobServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardAnalyticsService
{
    public function __construct(
        private InventoryServiceInterface $inventoryService,
        private JobServiceInterface $jobService,
    ) {}

    /**
     * Get admin dashboard metrics.
     */
    public function getAdminMetrics(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now();

        return Cache::remember("analytics.admin.{$from->format('Y-m-d')}.{$to->format('Y-m-d')}", 300, function () use ($from, $to) {
            return [
                'orders' => $this->getOrderMetrics($from, $to),
                'jobs' => $this->getJobMetrics($from, $to),
                'revenue' => $this->getRevenueMetrics($from, $to),
                'inventory' => $this->getInventoryMetrics(),
                'entities' => $this->getEntityCounts(),
            ];
        });
    }

    /**
     * Get brand dashboard metrics.
     */
    public function getBrandMetrics(int $brandId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now();

        return [
            'orders' => $this->getBrandOrderMetrics($brandId, $from, $to),
            'revenue' => $this->getBrandRevenueMetrics($brandId, $from, $to),
            'products' => $this->getBrandProductMetrics($brandId),
            'stock' => $this->getBrandStockMetrics($brandId),
        ];
    }

    /**
     * Get venue dashboard metrics.
     */
    public function getVenueMetrics(int $venueId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now();

        return [
            'orders' => $this->getVenueOrderMetrics($venueId, $from, $to),
            'spending' => $this->getVenueSpendingMetrics($venueId, $from, $to),
            'jobs' => $this->getVenueJobMetrics($venueId, $from, $to),
        ];
    }

    /**
     * Get producer dashboard metrics.
     */
    public function getProducerMetrics(int $producerId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now();

        return [
            'jobs' => $this->getProducerJobMetrics($producerId, $from, $to),
            'earnings' => $this->getProducerEarningsMetrics($producerId, $from, $to),
            'orders' => $this->getProducerOrderMetrics($producerId, $from, $to),
        ];
    }

    // ========== Order Metrics ==========

    private function getOrderMetrics(Carbon $from, Carbon $to): array
    {
        $orders = Order::whereBetween('created_at', [$from, $to]);

        return [
            'total' => $orders->count(),
            'total_value' => $orders->sum('grand_total'),
            'average_value' => $orders->avg('grand_total') ?? 0,
            'by_status' => Order::whereBetween('created_at', [$from, $to])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_day' => Order::whereBetween('created_at', [$from, $to])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'), DB::raw('sum(grand_total) as total'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => [
                    'date' => $row->date,
                    'count' => $row->count,
                    'total' => $row->total,
                ])
                ->toArray(),
        ];
    }

    private function getBrandOrderMetrics(int $brandId, Carbon $from, Carbon $to): array
    {
        $orders = BrandOrder::where('brand_id', $brandId)
            ->whereBetween('created_at', [$from, $to]);

        return [
            'total' => $orders->count(),
            'total_value' => $orders->sum('grand_total'),
            'net_revenue' => $orders->sum('net_to_brand'),
            'pending' => BrandOrder::where('brand_id', $brandId)
                ->where('status', 'pending')
                ->count(),
        ];
    }

    private function getVenueOrderMetrics(int $venueId, Carbon $from, Carbon $to): array
    {
        $orders = Order::where('venue_id', $venueId)
            ->whereBetween('created_at', [$from, $to]);

        return [
            'total' => $orders->count(),
            'total_spent' => $orders->sum('grand_total'),
            'pending' => Order::where('venue_id', $venueId)
                ->whereIn('status', ['pending', 'confirmed', 'processing'])
                ->count(),
        ];
    }

    private function getProducerOrderMetrics(int $producerId, Carbon $from, Carbon $to): array
    {
        return [
            'orders_created' => Order::where('producer_id', $producerId)
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'total_value' => Order::where('producer_id', $producerId)
                ->whereBetween('created_at', [$from, $to])
                ->sum('grand_total'),
        ];
    }

    // ========== Job Metrics ==========

    private function getJobMetrics(Carbon $from, Carbon $to): array
    {
        return [
            'total' => Job::whereBetween('created_at', [$from, $to])->count(),
            'completed' => Job::whereBetween('created_at', [$from, $to])
                ->where('status', 'completed')
                ->count(),
            'total_hours' => Job::whereBetween('created_at', [$from, $to])
                ->with('timeEntries')
                ->get()
                ->sum(fn ($job) => $job->timeEntries->sum('duration_minutes') / 60),
            'by_type' => Job::whereBetween('created_at', [$from, $to])
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    private function getVenueJobMetrics(int $venueId, Carbon $from, Carbon $to): array
    {
        return [
            'total' => Job::where('venue_id', $venueId)
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'completed' => Job::where('venue_id', $venueId)
                ->whereBetween('created_at', [$from, $to])
                ->where('status', 'completed')
                ->count(),
            'upcoming' => Job::where('venue_id', $venueId)
                ->where('status', 'scheduled')
                ->where('scheduled_start', '>', now())
                ->count(),
        ];
    }

    private function getProducerJobMetrics(int $producerId, Carbon $from, Carbon $to): array
    {
        $jobs = Job::where('producer_id', $producerId)
            ->whereBetween('created_at', [$from, $to]);

        $totalMinutes = Job::where('producer_id', $producerId)
            ->whereBetween('created_at', [$from, $to])
            ->with('timeEntries')
            ->get()
            ->sum(fn ($job) => $job->timeEntries->sum('duration_minutes'));

        return [
            'completed' => $jobs->clone()->where('status', 'completed')->count(),
            'total' => $jobs->count(),
            'total_hours' => round($totalMinutes / 60, 1),
            'upcoming' => Job::where('producer_id', $producerId)
                ->where('status', 'scheduled')
                ->where('scheduled_start', '>', now())
                ->count(),
        ];
    }

    // ========== Revenue Metrics ==========

    private function getRevenueMetrics(Carbon $from, Carbon $to): array
    {
        $brandOrders = BrandOrder::whereBetween('created_at', [$from, $to]);

        return [
            'gross' => $brandOrders->sum('grand_total'),
            'platform_fees' => $brandOrders->sum('platform_fee'),
            'commissions' => $brandOrders->sum('commission_amount'),
            'net_to_brands' => $brandOrders->sum('net_to_brand'),
        ];
    }

    private function getBrandRevenueMetrics(int $brandId, Carbon $from, Carbon $to): array
    {
        $orders = BrandOrder::where('brand_id', $brandId)
            ->whereBetween('created_at', [$from, $to]);

        return [
            'gross' => $orders->sum('grand_total'),
            'net' => $orders->sum('net_to_brand'),
            'platform_fees_paid' => $orders->sum('platform_fee'),
            'by_day' => BrandOrder::where('brand_id', $brandId)
                ->whereBetween('created_at', [$from, $to])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(net_to_brand) as total'))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date')
                ->toArray(),
        ];
    }

    private function getVenueSpendingMetrics(int $venueId, Carbon $from, Carbon $to): array
    {
        return [
            'total' => Order::where('venue_id', $venueId)
                ->whereBetween('created_at', [$from, $to])
                ->sum('grand_total'),
            'by_brand' => BrandOrder::whereHas('order', fn ($q) => $q->where('venue_id', $venueId))
                ->whereBetween('created_at', [$from, $to])
                ->select('brand_id', DB::raw('sum(grand_total) as total'))
                ->groupBy('brand_id')
                ->with('brand:id,name')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->brand->name => $row->total])
                ->toArray(),
        ];
    }

    private function getProducerEarningsMetrics(int $producerId, Carbon $from, Carbon $to): array
    {
        $billing = BillingRecord::where('producer_id', $producerId)
            ->whereBetween('created_at', [$from, $to]);

        return [
            'total_earned' => $billing->where('type', 'producer_time')->sum('amount'),
            'pending' => $billing->clone()->where('status', 'pending')->sum('amount'),
            'paid' => $billing->clone()->where('status', 'paid')->sum('amount'),
        ];
    }

    // ========== Inventory Metrics ==========

    private function getInventoryMetrics(): array
    {
        return [
            'total_products' => Product::where('is_active', true)->count(),
            'low_stock' => StockLevel::where('quantity_available', '<', DB::raw('reorder_level'))->count(),
            'out_of_stock' => StockLevel::where('quantity_available', '<=', 0)->count(),
            'total_value' => StockLevel::join('products', 'stock_levels.product_id', '=', 'products.id')
                ->select(DB::raw('SUM(stock_levels.quantity_on_hand * products.unit_price) as value'))
                ->first()
                ->value ?? 0,
        ];
    }

    private function getBrandProductMetrics(int $brandId): array
    {
        return [
            'total' => Product::where('brand_id', $brandId)->count(),
            'active' => Product::where('brand_id', $brandId)->where('is_active', true)->count(),
        ];
    }

    private function getBrandStockMetrics(int $brandId): array
    {
        $productIds = Product::where('brand_id', $brandId)->pluck('id');

        return [
            'total_units' => StockLevel::whereIn('product_id', $productIds)->sum('quantity_on_hand'),
            'low_stock_count' => StockLevel::whereIn('product_id', $productIds)
                ->where('quantity_available', '<', DB::raw('reorder_level'))
                ->count(),
        ];
    }

    // ========== Entity Counts ==========

    private function getEntityCounts(): array
    {
        return [
            'brands' => \App\Models\Brand::count(),
            'venues' => \App\Models\Venue::count(),
            'producers' => \App\Models\Producer::count(),
            'three_pls' => \App\Models\ThreePL::count(),
        ];
    }
}
