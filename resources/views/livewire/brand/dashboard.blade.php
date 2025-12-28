<?php

use App\Models\BrandOrder;
use App\Models\Product;
use App\Enums\OrderStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Brand Dashboard')] class extends Component {
    public function with(): array
    {
        $brand = auth()->user()->brand;

        if (!$brand) {
            return ['brand' => null];
        }

        return [
            'brand' => $brand,
            'products' => $brand->products()->count(),
            'activeProducts' => $brand->products()->where('is_active', true)->count(),
            'orders' => BrandOrder::where('brand_id', $brand->id)->count(),
            'pendingOrders' => BrandOrder::where('brand_id', $brand->id)
                ->whereIn('status', [OrderStatus::Pending, OrderStatus::Confirmed])
                ->count(),
            'totalRevenue' => BrandOrder::where('brand_id', $brand->id)->sum('net_to_brand'),
            'recentOrders' => BrandOrder::where('brand_id', $brand->id)
                ->with(['order.venue'])
                ->latest()
                ->take(5)
                ->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    @if($brand)
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $brand->name }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Brand Dashboard</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $products }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Products</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $orders }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Orders</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $pendingOrders }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Orders</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($totalRevenue, 2) }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Net Revenue</p>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Recent Orders</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($recentOrders as $order)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $order->order?->venue?->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-emerald-600 dark:text-emerald-400">${{ number_format($order->net_to_brand, 2) }}</p>
                            <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">No orders yet</div>
                @endforelse
            </div>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your Brand</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a brand profile to get started.</p>
        </div>
    @endif
</div>
