<?php

use App\Models\Brand;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Brands - Admin')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(): array
    {
        $query = Brand::withCount(['products', 'brandOrders'])
            ->withSum('brandOrders', 'net_to_brand');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        return [
            'brands' => $query->latest()->paginate(20),
            'totalBrands' => Brand::count(),
            'totalProducts' => \App\Models\Product::count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Brands</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ $totalBrands }} brands, {{ $totalProducts }} products</p>
        </div>
    </div>

    <!-- Search -->
    <div class="max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search brands..." icon="magnifying-glass" />
    </div>

    <!-- Brands Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($brands as $brand)
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $brand->name }}</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $brand->email }}</p>
                    </div>
                    @if($brand->is_active)
                        <flux:badge size="sm" color="emerald">Active</flux:badge>
                    @else
                        <flux:badge size="sm" color="zinc">Inactive</flux:badge>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $brand->products_count }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Products</p>
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $brand->brand_orders_count }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Orders</p>
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">${{ number_format($brand->brand_orders_sum_net_to_brand ?? 0, 0) }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Revenue</p>
                    </div>
                </div>

                @if($brand->commission_rate)
                    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Commission: {{ $brand->commission_rate }}% |
                            Platform Fee: {{ $brand->platform_fee_rate ?? 5 }}%
                        </p>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <p class="text-zinc-500 dark:text-zinc-400">No brands found</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $brands->links() }}
    </div>
</div>
