<?php

use App\Models\BrandOrder;
use App\Enums\FulfilmentStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Fulfilment Queue')] class extends Component {
    use WithPagination;

    public string $status = 'pending';
    public string $search = '';

    public function with(): array
    {
        $threePL = auth()->user()->threePL;

        if (!$threePL) {
            return ['orders' => collect(), 'threePL' => null];
        }

        $warehouseIds = $threePL->warehouses->pluck('id');

        $query = BrandOrder::whereIn('warehouse_id', $warehouseIds)
            ->with(['brand', 'order.venue', 'warehouse', 'orderLines.product']);

        if ($this->status === 'pending') {
            $query->whereIn('fulfilment_status', [FulfilmentStatus::Pending, FulfilmentStatus::Assigned]);
        } elseif ($this->status === 'picking') {
            $query->where('fulfilment_status', FulfilmentStatus::Picking);
        } elseif ($this->status === 'packed') {
            $query->where('fulfilment_status', FulfilmentStatus::Packed);
        } elseif ($this->status === 'dispatched') {
            $query->where('fulfilment_status', FulfilmentStatus::Dispatched);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")
                    ->orWhereHas('brand', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('order.venue', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        return [
            'threePL' => $threePL,
            'orders' => $query->latest()->paginate(20),
            'counts' => [
                'pending' => BrandOrder::whereIn('warehouse_id', $warehouseIds)
                    ->whereIn('fulfilment_status', [FulfilmentStatus::Pending, FulfilmentStatus::Assigned])
                    ->count(),
                'picking' => BrandOrder::whereIn('warehouse_id', $warehouseIds)
                    ->where('fulfilment_status', FulfilmentStatus::Picking)
                    ->count(),
                'packed' => BrandOrder::whereIn('warehouse_id', $warehouseIds)
                    ->where('fulfilment_status', FulfilmentStatus::Packed)
                    ->count(),
                'dispatched' => BrandOrder::whereIn('warehouse_id', $warehouseIds)
                    ->where('fulfilment_status', FulfilmentStatus::Dispatched)
                    ->count(),
            ],
        ];
    }

    public function startPicking(int $orderId): void
    {
        $order = BrandOrder::findOrFail($orderId);
        $order->update([
            'fulfilment_status' => FulfilmentStatus::Picking,
            'picked_at' => null,
        ]);
    }

    public function markPicked(int $orderId): void
    {
        $order = BrandOrder::findOrFail($orderId);
        $order->update([
            'fulfilment_status' => FulfilmentStatus::Packed,
            'picked_at' => now(),
        ]);
    }

    public function dispatch(int $orderId): void
    {
        $order = BrandOrder::findOrFail($orderId);
        $order->update([
            'fulfilment_status' => FulfilmentStatus::Dispatched,
            'dispatched_at' => now(),
        ]);
    }
}; ?>

<div class="space-y-6">
    @if($threePL)
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Fulfilment Queue</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Pick, pack, and dispatch orders</p>
            </div>
        </div>

        <!-- Status Tabs -->
        <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-4">
            <button
                wire:click="$set('status', 'pending')"
                class="px-4 py-2 rounded-lg font-medium transition {{ $status === 'pending' ? 'bg-amber-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
            >
                Pending <span class="ml-1 text-sm opacity-75">({{ $counts['pending'] }})</span>
            </button>
            <button
                wire:click="$set('status', 'picking')"
                class="px-4 py-2 rounded-lg font-medium transition {{ $status === 'picking' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
            >
                Picking <span class="ml-1 text-sm opacity-75">({{ $counts['picking'] }})</span>
            </button>
            <button
                wire:click="$set('status', 'packed')"
                class="px-4 py-2 rounded-lg font-medium transition {{ $status === 'packed' ? 'bg-purple-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
            >
                Packed <span class="ml-1 text-sm opacity-75">({{ $counts['packed'] }})</span>
            </button>
            <button
                wire:click="$set('status', 'dispatched')"
                class="px-4 py-2 rounded-lg font-medium transition {{ $status === 'dispatched' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
            >
                Dispatched <span class="ml-1 text-sm opacity-75">({{ $counts['dispatched'] }})</span>
            </button>
        </div>

        <!-- Search -->
        <div class="max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search orders..." icon="magnifying-glass" />
        </div>

        <!-- Orders List -->
        <div class="space-y-4">
            @forelse($orders as $order)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                    <!-- Order Header -->
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $order->order_number }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $order->brand?->name }} → {{ $order->order?->venue?->name }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:badge size="sm" :color="$order->fulfilment_status->color()">
                                {{ $order->fulfilment_status->label() }}
                            </flux:badge>
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $order->warehouse?->name }}
                            </span>
                        </div>
                    </div>

                    <!-- Order Lines -->
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-zinc-500 dark:text-zinc-400">
                                    <th class="pb-2 font-medium">Product</th>
                                    <th class="pb-2 font-medium text-center">SKU</th>
                                    <th class="pb-2 font-medium text-center">Qty</th>
                                    <th class="pb-2 font-medium text-right">Location</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($order->orderLines as $line)
                                    <tr>
                                        <td class="py-2 text-zinc-900 dark:text-white">{{ $line->product?->name }}</td>
                                        <td class="py-2 text-center text-zinc-500 dark:text-zinc-400">{{ $line->product?->sku }}</td>
                                        <td class="py-2 text-center font-medium text-zinc-900 dark:text-white">{{ $line->quantity }}</td>
                                        <td class="py-2 text-right text-zinc-500 dark:text-zinc-400">
                                            {{ $line->product?->stockLevels->first()?->bin_location ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            @if($order->picked_at)
                                Picked: {{ $order->picked_at->format('M j, g:i A') }}
                            @endif
                            @if($order->dispatched_at)
                                | Dispatched: {{ $order->dispatched_at->format('M j, g:i A') }}
                            @endif
                        </div>
                        <div class="flex gap-2">
                            @if($order->fulfilment_status === \App\Enums\FulfilmentStatus::Pending || $order->fulfilment_status === \App\Enums\FulfilmentStatus::Assigned)
                                <flux:button wire:click="startPicking({{ $order->id }})" variant="primary" size="sm">
                                    Start Picking
                                </flux:button>
                            @elseif($order->fulfilment_status === \App\Enums\FulfilmentStatus::Picking)
                                <flux:button wire:click="markPicked({{ $order->id }})" variant="primary" size="sm">
                                    Mark as Packed
                                </flux:button>
                            @elseif($order->fulfilment_status === \App\Enums\FulfilmentStatus::Packed)
                                <flux:button wire:click="dispatch({{ $order->id }})" variant="primary" size="sm">
                                    Dispatch
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400">No orders in this status</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your 3PL Profile</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a 3PL profile to manage fulfilment.</p>
        </div>
    @endif
</div>
