<?php

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('My Products')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(): array
    {
        $brand = auth()->user()->brand;

        if (!$brand) {
            return ['brand' => null, 'products' => collect()];
        }

        $query = $brand->products()->with('stockLevels');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%");
            });
        }

        return [
            'brand' => $brand,
            'products' => $query->latest()->paginate(20),
            'totalProducts' => $brand->products()->count(),
            'activeProducts' => $brand->products()->where('is_active', true)->count(),
        ];
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_active' => !$product->is_active]);
    }
}; ?>

<div class="space-y-6">
    @if($brand)
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Products</h1>
                <p class="text-zinc-500 dark:text-zinc-400">{{ $activeProducts }} active of {{ $totalProducts }} total</p>
            </div>
        </div>

        <!-- Search -->
        <div class="max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" />
        </div>

        <!-- Products Table -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Category</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Price</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Stock</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($products as $product)
                        @php
                            $totalStock = $product->stockLevels->sum('quantity_available');
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
                                {{ $product->category }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-zinc-900 dark:text-white">
                                ${{ number_format($product->wholesale_price, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center {{ $totalStock === 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                {{ $totalStock }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($product->is_active)
                                    <flux:badge size="sm" color="emerald">Active</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Inactive</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button wire:click="toggleActive({{ $product->id }})" size="sm" variant="ghost">
                                    {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                </flux:button>
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
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your Brand</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a brand profile to manage products.</p>
        </div>
    @endif
</div>
