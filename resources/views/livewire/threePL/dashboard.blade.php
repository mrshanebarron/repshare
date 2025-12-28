<?php

use App\Models\BrandOrder;
use App\Models\Warehouse;
use App\Models\StockLevel;
use App\Enums\FulfilmentStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('3PL Dashboard')] class extends Component {
    public function with(): array
    {
        $threePL = auth()->user()->threePL;

        if (!$threePL) {
            return ['threePL' => null];
        }

        $warehouseIds = $threePL->warehouses->pluck('id');

        return [
            'threePL' => $threePL,
            'warehouses' => $threePL->warehouses,
            'pendingFulfilment' => BrandOrder::whereIn('warehouse_id', $warehouseIds)
                ->whereIn('fulfilment_status', [FulfilmentStatus::Pending, FulfilmentStatus::Assigned])
                ->count(),
            'inProgress' => BrandOrder::whereIn('warehouse_id', $warehouseIds)
                ->whereIn('fulfilment_status', [FulfilmentStatus::Picking, FulfilmentStatus::Packed])
                ->count(),
            'dispatched' => BrandOrder::whereIn('warehouse_id', $warehouseIds)
                ->where('fulfilment_status', FulfilmentStatus::Dispatched)
                ->count(),
            'lowStock' => StockLevel::whereIn('warehouse_id', $warehouseIds)
                ->whereColumn('quantity_available', '<=', 'reorder_point')
                ->count(),
            'pendingOrders' => BrandOrder::whereIn('warehouse_id', $warehouseIds)
                ->whereIn('fulfilment_status', [FulfilmentStatus::Pending, FulfilmentStatus::Assigned])
                ->with(['brand', 'order.venue'])
                ->latest()
                ->take(10)
                ->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    @if($threePL)
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $threePL->name }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Fulfilment Dashboard</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $pendingFulfilment }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending Fulfilment</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $inProgress }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">In Progress</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $dispatched }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Dispatched Today</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $lowStock }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Low Stock Alerts</p>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Pending Fulfilment</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($pendingOrders as $order)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $order->brand?->name }} → {{ $order->order?->venue?->name }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" :color="$order->fulfilment_status->color()">
                                {{ $order->fulfilment_status->label() }}
                            </flux:badge>
                            <flux:button size="sm" variant="primary">Pick</flux:button>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">No pending orders</div>
                @endforelse
            </div>
        </div>

        <!-- Warehouses -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="font-semibold text-zinc-900 dark:text-white mb-4">Warehouses</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($warehouses as $warehouse)
                    <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-700/50">
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $warehouse->name }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $warehouse->fullAddress() }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Code: {{ $warehouse->code }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your 3PL Profile</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a 3PL profile to manage fulfilment.</p>
        </div>
    @endif
</div>
