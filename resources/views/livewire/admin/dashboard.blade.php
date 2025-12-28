<?php

use App\Models\Order;
use App\Models\Brand;
use App\Models\Venue;
use App\Models\Producer;
use App\Models\Product;
use App\Models\Job;
use App\Models\BrandOrder;
use App\Enums\OrderStatus;
use App\Enums\JobStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Admin Dashboard')] class extends Component {
    public function with(): array
    {
        $recentOrders = Order::with(['venue', 'producer'])
            ->latest()
            ->take(5)
            ->get();

        $todaysJobs = Job::with(['producer', 'venue'])
            ->whereDate('scheduled_start', today())
            ->orderBy('scheduled_start')
            ->take(5)
            ->get();

        return [
            'stats' => [
                'orders' => Order::count(),
                'brands' => Brand::count(),
                'venues' => Venue::count(),
                'producers' => Producer::count(),
                'products' => Product::count(),
                'activeJobs' => Job::whereIn('status', [JobStatus::Scheduled, JobStatus::InProgress])->count(),
            ],
            'recentOrders' => $recentOrders,
            'todaysJobs' => $todaysJobs,
            'ordersByStatus' => Order::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'totalRevenue' => Order::sum('grand_total'),
            'platformFees' => Order::sum('platform_fee'),
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Admin Dashboard</h1>
        <p class="text-zinc-500 dark:text-zinc-400">Overview of the RepShare marketplace</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <flux:icon.shopping-cart class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['orders'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Orders</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <flux:icon.building-storefront class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['brands'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Brands</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <flux:icon.map-pin class="size-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['venues'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Venues</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <flux:icon.user-group class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['producers'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Producers</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
                    <flux:icon.cube class="size-5 text-cyan-600 dark:text-cyan-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['products'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Products</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-rose-100 dark:bg-rose-900/30 rounded-lg">
                    <flux:icon.calendar class="size-5 text-rose-600 dark:text-rose-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['activeJobs'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Active Jobs</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Revenue</h3>
            <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">${{ number_format($totalRevenue, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Platform Fees Earned</h3>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">${{ number_format($platformFees, 2) }}</p>
        </div>
    </div>

    <!-- Recent Orders & Today's Jobs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Recent Orders</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($recentOrders as $order)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $order->venue?->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-zinc-900 dark:text-white">${{ number_format($order->grand_total, 2) }}</p>
                            <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">No orders yet</div>
                @endforelse
            </div>
        </div>

        <!-- Today's Jobs -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Today's Jobs</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($todaysJobs as $job)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $job->venue?->name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $job->producer?->name }} - {{ $job->type->label() }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $job->scheduled_start?->format('g:i A') }}</p>
                            <flux:badge size="sm" :color="$job->status->color()">{{ $job->status->label() }}</flux:badge>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">No jobs scheduled today</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Integration Status -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
        <h2 class="font-semibold text-zinc-900 dark:text-white mb-4">Integration Status</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50">
                <div class="p-2 rounded-full {{ config('services.inventory.driver') === 'unleashed' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-amber-100 dark:bg-amber-900/30' }}">
                    <flux:icon.cube class="size-4 {{ config('services.inventory.driver') === 'unleashed' ? 'text-green-600' : 'text-amber-600' }}" />
                </div>
                <div>
                    <p class="font-medium text-zinc-900 dark:text-white">Unleashed</p>
                    <p class="text-xs {{ config('services.inventory.driver') === 'unleashed' ? 'text-green-600' : 'text-amber-600' }}">
                        {{ config('services.inventory.driver') === 'unleashed' ? 'Connected' : 'Using Dummy Data' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50">
                <div class="p-2 rounded-full {{ config('services.jobs.driver') === 'geoop' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-amber-100 dark:bg-amber-900/30' }}">
                    <flux:icon.map class="size-4 {{ config('services.jobs.driver') === 'geoop' ? 'text-green-600' : 'text-amber-600' }}" />
                </div>
                <div>
                    <p class="font-medium text-zinc-900 dark:text-white">GeoOp</p>
                    <p class="text-xs {{ config('services.jobs.driver') === 'geoop' ? 'text-green-600' : 'text-amber-600' }}">
                        {{ config('services.jobs.driver') === 'geoop' ? 'Connected' : 'Using Dummy Data' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/50">
                <div class="p-2 rounded-full bg-zinc-200 dark:bg-zinc-600">
                    <flux:icon.link class="size-4 text-zinc-600 dark:text-zinc-300" />
                </div>
                <div>
                    <p class="font-medium text-zinc-900 dark:text-white">ALM Connect</p>
                    <p class="text-xs text-zinc-500">Not Configured</p>
                </div>
            </div>
        </div>
    </div>
</div>
