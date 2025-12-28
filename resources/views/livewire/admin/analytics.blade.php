<?php

use App\Models\Order;
use App\Models\BrandOrder;
use App\Models\Brand;
use App\Models\Venue;
use App\Models\Product;
use App\Models\Job;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] #[Title('Analytics - Admin')] class extends Component {
    public string $period = '30';

    public function with(): array
    {
        $startDate = match ($this->period) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subDays(365),
            default => now()->subDays(30),
        };

        $previousStart = match ($this->period) {
            '7' => now()->subDays(14),
            '30' => now()->subDays(60),
            '90' => now()->subDays(180),
            '365' => now()->subDays(730),
            default => now()->subDays(60),
        };

        // Current period stats
        $currentRevenue = Order::where('created_at', '>=', $startDate)->sum('grand_total');
        $currentOrders = Order::where('created_at', '>=', $startDate)->count();
        $currentFees = Order::where('created_at', '>=', $startDate)->sum('platform_fee');

        // Previous period stats
        $previousRevenue = Order::whereBetween('created_at', [$previousStart, $startDate])->sum('grand_total');
        $previousOrders = Order::whereBetween('created_at', [$previousStart, $startDate])->count();
        $previousFees = Order::whereBetween('created_at', [$previousStart, $startDate])->sum('platform_fee');

        // Calculate changes
        $revenueChange = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        $ordersChange = $previousOrders > 0 ? (($currentOrders - $previousOrders) / $previousOrders) * 100 : 0;
        $feesChange = $previousFees > 0 ? (($currentFees - $previousFees) / $previousFees) * 100 : 0;

        // Top brands by revenue
        $topBrands = BrandOrder::where('created_at', '>=', $startDate)
            ->selectRaw('brand_id, SUM(subtotal) as total, COUNT(*) as orders')
            ->groupBy('brand_id')
            ->with('brand')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // Top venues by spend
        $topVenues = Order::where('created_at', '>=', $startDate)
            ->selectRaw('venue_id, SUM(grand_total) as total, COUNT(*) as orders')
            ->groupBy('venue_id')
            ->with('venue')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // Top products
        $topProducts = \App\Models\OrderLine::where('created_at', '>=', $startDate)
            ->selectRaw('product_id, SUM(quantity) as units, SUM(line_total) as revenue')
            ->groupBy('product_id')
            ->with('product.brand')
            ->orderByDesc('revenue')
            ->take(10)
            ->get();

        // Daily revenue for chart
        $dailyRevenue = Order::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'currentRevenue' => $currentRevenue,
            'currentOrders' => $currentOrders,
            'currentFees' => $currentFees,
            'revenueChange' => $revenueChange,
            'ordersChange' => $ordersChange,
            'feesChange' => $feesChange,
            'topBrands' => $topBrands,
            'topVenues' => $topVenues,
            'topProducts' => $topProducts,
            'dailyRevenue' => $dailyRevenue,
            'totalBrands' => Brand::count(),
            'totalVenues' => Venue::count(),
            'totalProducts' => Product::where('is_active', true)->count(),
            'totalJobs' => Job::where('created_at', '>=', $startDate)->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Analytics</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Platform performance and insights</p>
        </div>
        <flux:select wire:model.live="period" class="w-40">
            <option value="7">Last 7 Days</option>
            <option value="30">Last 30 Days</option>
            <option value="90">Last 90 Days</option>
            <option value="365">Last Year</option>
        </flux:select>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Revenue</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">${{ number_format($currentRevenue, 2) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon name="currency-dollar" class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm">
                @if($revenueChange >= 0)
                    <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($revenueChange, 1) }}%</span>
                @else
                    <span class="text-red-600 dark:text-red-400">{{ number_format($revenueChange, 1) }}%</span>
                @endif
                <span class="ml-1 text-zinc-500 dark:text-zinc-400">vs previous period</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Orders</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($currentOrders) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="shopping-cart" class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm">
                @if($ordersChange >= 0)
                    <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($ordersChange, 1) }}%</span>
                @else
                    <span class="text-red-600 dark:text-red-400">{{ number_format($ordersChange, 1) }}%</span>
                @endif
                <span class="ml-1 text-zinc-500 dark:text-zinc-400">vs previous period</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Platform Fees</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($currentFees, 2) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <flux:icon name="banknotes" class="size-5 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm">
                @if($feesChange >= 0)
                    <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($feesChange, 1) }}%</span>
                @else
                    <span class="text-red-600 dark:text-red-400">{{ number_format($feesChange, 1) }}%</span>
                @endif
                <span class="ml-1 text-zinc-500 dark:text-zinc-400">vs previous period</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Jobs Completed</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalJobs) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="briefcase" class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
            <div class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $totalBrands }} brands, {{ $totalVenues }} venues
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Brands -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Top Brands by Revenue</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($topBrands as $item)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $item->brand?->name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $item->orders }} orders</p>
                        </div>
                        <p class="font-semibold text-emerald-600 dark:text-emerald-400">${{ number_format($item->total, 2) }}</p>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">No data yet</div>
                @endforelse
            </div>
        </div>

        <!-- Top Venues -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Top Venues by Spend</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($topVenues as $item)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $item->venue?->name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $item->orders }} orders</p>
                        </div>
                        <p class="font-semibold text-zinc-900 dark:text-white">${{ number_format($item->total, 2) }}</p>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">No data yet</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="font-semibold text-zinc-900 dark:text-white">Top Products</h2>
        </div>
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Brand</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Units Sold</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($topProducts as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $item->product?->name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $item->product?->brand?->name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-zinc-900 dark:text-white">
                            {{ number_format($item->units) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-emerald-600 dark:text-emerald-400">
                            ${{ number_format($item->revenue, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">No data yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
