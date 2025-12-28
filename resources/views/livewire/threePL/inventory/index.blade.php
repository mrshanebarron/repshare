<?php

use App\Models\StockLevel;
use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Inventory Management')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filter = 'all';
    public ?int $warehouseId = null;

    public function with(): array
    {
        $threePL = auth()->user()->threePL;

        if (!$threePL) {
            return ['stockLevels' => collect(), 'threePL' => null, 'warehouses' => collect()];
        }

        $warehouseIds = $threePL->warehouses->pluck('id');

        $query = StockLevel::whereIn('warehouse_id', $warehouseIds)
            ->with(['product.brand', 'warehouse']);

        if ($this->warehouseId) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        if ($this->filter === 'low') {
            $query->whereColumn('quantity_available', '<=', 'reorder_point');
        } elseif ($this->filter === 'out') {
            $query->where('quantity_available', 0);
        }

        if ($this->search) {
            $query->whereHas('product', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%");
            });
        }

        return [
            'threePL' => $threePL,
            'warehouses' => $threePL->warehouses,
            'stockLevels' => $query->orderBy('quantity_available', 'asc')->paginate(25),
            'stats' => [
                'total' => StockLevel::whereIn('warehouse_id', $warehouseIds)->count(),
                'low' => StockLevel::whereIn('warehouse_id', $warehouseIds)
                    ->whereColumn('quantity_available', '<=', 'reorder_point')
                    ->count(),
                'out' => StockLevel::whereIn('warehouse_id', $warehouseIds)
                    ->where('quantity_available', 0)
                    ->count(),
            ],
        ];
    }

    public function adjustStock(int $stockLevelId, int $adjustment, string $reason): void
    {
        $stockLevel = StockLevel::findOrFail($stockLevelId);
        $stockLevel->update([
            'quantity_available' => max(0, $stockLevel->quantity_available + $adjustment),
        ]);

        // Log the adjustment
        activity()
            ->performedOn($stockLevel)
            ->withProperties([
                'adjustment' => $adjustment,
                'reason' => $reason,
                'new_quantity' => $stockLevel->quantity_available,
            ])
            ->log('stock_adjusted');
    }
}; ?>

<div class="space-y-6">
    @if($threePL)
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Inventory Management</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Monitor and adjust stock levels across warehouses</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['total'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total SKUs</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['low'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Low Stock</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['out'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Out of Stock</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 max-w-md">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" />
            </div>
            <flux:select wire:model.live="warehouseId" class="w-48">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </flux:select>
            <div class="flex gap-2">
                <button
                    wire:click="$set('filter', 'all')"
                    class="px-4 py-2 rounded-lg font-medium transition {{ $filter === 'all' ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}"
                >
                    All
                </button>
                <button
                    wire:click="$set('filter', 'low')"
                    class="px-4 py-2 rounded-lg font-medium transition {{ $filter === 'low' ? 'bg-amber-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}"
                >
                    Low Stock
                </button>
                <button
                    wire:click="$set('filter', 'out')"
                    class="px-4 py-2 rounded-lg font-medium transition {{ $filter === 'out' ? 'bg-red-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}"
                >
                    Out of Stock
                </button>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Brand</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Warehouse</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Available</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Committed</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Reorder Point</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Location</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($stockLevels as $stock)
                        @php
                            $isLow = $stock->quantity_available <= $stock->reorder_point && $stock->quantity_available > 0;
                            $isOut = $stock->quantity_available === 0;
                        @endphp
                        <tr class="{{ $isOut ? 'bg-red-50 dark:bg-red-900/10' : ($isLow ? 'bg-amber-50 dark:bg-amber-900/10' : '') }}">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $stock->product?->name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $stock->product?->sku }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $stock->product?->brand?->name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $stock->warehouse?->name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center font-semibold {{ $isOut ? 'text-red-600 dark:text-red-400' : ($isLow ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-white') }}">
                                {{ $stock->quantity_available }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-zinc-500 dark:text-zinc-400">
                                {{ $stock->quantity_committed }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-zinc-500 dark:text-zinc-400">
                                {{ $stock->reorder_point }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $stock->bin_location ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($isOut)
                                    <flux:badge size="sm" color="red">Out of Stock</flux:badge>
                                @elseif($isLow)
                                    <flux:badge size="sm" color="amber">Low Stock</flux:badge>
                                @else
                                    <flux:badge size="sm" color="emerald">In Stock</flux:badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No inventory found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $stockLevels->links() }}
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your 3PL Profile</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a 3PL profile to manage inventory.</p>
        </div>
    @endif
</div>
