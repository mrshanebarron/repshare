<?php

use App\Models\Order;
use App\Models\Job;
use App\Enums\OrderStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Venue Dashboard')] class extends Component {
    public function with(): array
    {
        $venue = auth()->user()->venue;

        if (!$venue) {
            return ['venue' => null];
        }

        return [
            'venue' => $venue,
            'totalOrders' => Order::where('venue_id', $venue->id)->count(),
            'pendingOrders' => Order::where('venue_id', $venue->id)
                ->whereIn('status', [OrderStatus::Pending, OrderStatus::Confirmed])
                ->count(),
            'totalSpent' => Order::where('venue_id', $venue->id)->sum('grand_total'),
            'upcomingVisits' => Job::where('venue_id', $venue->id)
                ->where('scheduled_start', '>=', now())
                ->count(),
            'recentOrders' => Order::where('venue_id', $venue->id)
                ->with('brandOrders.brand')
                ->latest()
                ->take(5)
                ->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    @if($venue)
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $venue->name }}</h1>
                <p class="text-zinc-500 dark:text-zinc-400">{{ $venue->fullAddress() }}</p>
            </div>
            <flux:button href="{{ route('venue.orders.create') }}" variant="primary" icon="plus">
                New Order
            </flux:button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalOrders }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Orders</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $pendingOrders }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">In Progress</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">${{ number_format($totalSpent, 2) }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Spent</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $upcomingVisits }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Upcoming Visits</p>
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
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $order->brandOrders->pluck('brand.name')->implode(', ') }}
                            </p>
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
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your Venue</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a venue profile to start ordering.</p>
        </div>
    @endif
</div>
