<?php

use App\Models\Order;
use App\Enums\OrderStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('My Sales')] class extends Component {
    use WithPagination;

    public function with(): array
    {
        $producer = auth()->user()->producer;

        if (!$producer) {
            return ['producer' => null, 'orders' => collect()];
        }

        return [
            'producer' => $producer,
            'orders' => Order::where('producer_id', $producer->id)
                ->with(['venue', 'brandOrders.brand'])
                ->latest()
                ->paginate(15),
            'stats' => [
                'totalOrders' => Order::where('producer_id', $producer->id)->count(),
                'totalRevenue' => Order::where('producer_id', $producer->id)->sum('grand_total'),
                'totalCommission' => Order::where('producer_id', $producer->id)->sum('producer_commission'),
            ],
        ];
    }
}; ?>

<div class="space-y-6">
    @if($producer)
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Sales</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Orders placed through your visits</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['totalOrders'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Orders</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">${{ number_format($stats['totalRevenue'], 2) }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Sales</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($stats['totalCommission'], 2) }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Your Commission</p>
            </div>
        </div>

        <!-- Orders -->
        <div class="space-y-4">
            @forelse($orders as $order)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $order->order_number }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $order->venue?->name }} | {{ $order->created_at->format('M j, Y g:i A') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="font-medium text-zinc-900 dark:text-white">${{ number_format($order->grand_total, 2) }}</p>
                                <p class="text-sm text-emerald-600 dark:text-emerald-400">
                                    +${{ number_format($order->producer_commission, 2) }} commission
                                </p>
                            </div>
                            <flux:badge size="sm" :color="$order->status->color()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </div>
                    </div>
                    <div class="px-4 pb-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($order->brandOrders as $brandOrder)
                                <span class="px-2 py-1 rounded bg-zinc-100 dark:bg-zinc-700 text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $brandOrder->brand?->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400">No sales yet</p>
                    <p class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">Complete tasting visits to generate orders</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your Profile</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a producer profile to view sales.</p>
        </div>
    @endif
</div>
