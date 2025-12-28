<?php

use App\Models\Order;
use App\Enums\OrderStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Orders - Admin')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function with(): array
    {
        $query = Order::with(['venue', 'brandOrders.brand', 'producer']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")
                    ->orWhereHas('venue', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        return [
            'orders' => $query->latest()->paginate(20),
            'stats' => [
                'total' => Order::count(),
                'pending' => Order::where('status', OrderStatus::Pending)->count(),
                'confirmed' => Order::where('status', OrderStatus::Confirmed)->count(),
                'delivered' => Order::where('status', OrderStatus::Delivered)->count(),
            ],
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Orders</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Manage all platform orders</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['total'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Orders</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['confirmed'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Confirmed</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['delivered'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Delivered</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-4">
        <div class="flex-1 max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search orders..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="status" class="w-48">
            <option value="">All Statuses</option>
            @foreach(\App\Enums\OrderStatus::cases() as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <!-- Orders Table -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Order #</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Venue</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Brands</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Producer</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Platform Fee</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($orders as $order)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $order->order_number }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                            {{ $order->venue?->name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $order->brandOrders->pluck('brand.name')->implode(', ') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $order->producer?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-zinc-900 dark:text-white">
                            ${{ number_format($order->grand_total, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-emerald-600 dark:text-emerald-400">
                            ${{ number_format($order->platform_fee, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" :color="$order->status->color()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-zinc-500 dark:text-zinc-400">
                            {{ $order->created_at->format('M j, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No orders found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
