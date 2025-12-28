<?php

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Products - Admin')] class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $brandId = null;

    public function with(): array
    {
        $query = Product::with(['brand', 'stockLevels']);

        if ($this->brandId) {
            $query->where('brand_id', $this->brandId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%");
            });
        }

        return [
            'products' => $query->latest()->paginate(25),
            'brands' => \App\Models\Brand::orderBy('name')->get(),
            'totalProducts' => Product::count(),
            'activeProducts' => Product::where('is_active', true)->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Products</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ $activeProducts }} active of {{ $totalProducts }} total</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-4">
        <div class="flex-1 max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="brandId" class="w-48">
            <option value="">All Brands</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
            @endforeach
        </flux:select>
    </div>

    <!-- Products Table -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Brand</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Category</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Price</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Stock</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($products as $product)
                    @php
                        $totalStock = $product->stockLevels->sum('quantity_available');
                        $lowStock = $product->stockLevels->contains(fn($s) => $s->quantity_available <= $s->reorder_point);
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $product->name }}</p>
                            @if($product->unit_size)
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $product->unit_size }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400 font-mono">
                            {{ $product->sku }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $product->brand?->name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $product->category }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-zinc-900 dark:text-white">
                            ${{ number_format($product->wholesale_price, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            @if($totalStock === 0)
                                <span class="text-red-600 dark:text-red-400 font-medium">Out</span>
                            @elseif($lowStock)
                                <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $totalStock }}</span>
                            @else
                                <span class="text-zinc-900 dark:text-white">{{ $totalStock }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($product->is_active)
                                <flux:badge size="sm" color="emerald">Active</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Inactive</flux:badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No products found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $products->links() }}
    </div>
</div>
