<?php

use App\Models\BrandOrder;
use App\Enums\OrderStatus;
use App\Enums\FulfilmentStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Brand Orders')] class extends Component {
    use WithPagination;

    public string $status = '';

    public function with(): array
    {
        $brand = auth()->user()->brand;

        if (!$brand) {
            return ['brand' => null, 'orders' => collect()];
        }

        $query = BrandOrder::where('brand_id', $brand->id)
            ->with(['order.venue', 'warehouse', 'orderLines.product']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return [
            'brand' => $brand,
            'orders' => $query->latest()->paginate(15),
            'stats' => [
                'pending' => BrandOrder::where('brand_id', $brand->id)->where('status', OrderStatus::Pending)->count(),
                'confirmed' => BrandOrder::where('brand_id', $brand->id)->where('status', OrderStatus::Confirmed)->count(),
                'revenue' => BrandOrder::where('brand_id', $brand->id)->sum('net_to_brand'),
            ],
        ];
    }
}; ?>

<div class="space-y-6">
    @if($brand)
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Orders</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Track orders for {{ $brand->name }}</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Pending</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['confirmed'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Confirmed</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($stats['revenue'], 2) }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Revenue</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex gap-2">
            <button
                wire:click="$set('status', '')"
                class="px-4 py-2 rounded-lg font-medium transition {{ $status === '' ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}"
            >
                All
            </button>
            @foreach(OrderStatus::cases() as $statusOption)
                <button
                    wire:click="$set('status', '{{ $statusOption->value }}')"
                    class="px-4 py-2 rounded-lg font-medium transition {{ $status === $statusOption->value ? 'bg-' . $statusOption->color() . '-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}"
                >
                    {{ $statusOption->label() }}
                </button>
            @endforeach
        </div>

        <!-- Orders -->
        <div class="space-y-4">
            @forelse($orders as $order)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                    <div class="p-4 flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700">
                        <div>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $order->order_number }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $order->order?->venue?->name }} | {{ $order->created_at->format('M j, Y') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="font-semibold text-emerald-600 dark:text-emerald-400">${{ number_format($order->net_to_brand, 2) }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Net to you</p>
                            </div>
                            <div class="flex flex-col gap-1 items-end">
                                <flux:badge size="sm" :color="$order->status->color()">
                                    {{ $order->status->label() }}
                                </flux:badge>
                                <flux:badge size="sm" :color="$order->fulfilment_status->color()">
                                    {{ $order->fulfilment_status->label() }}
                                </flux:badge>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-zinc-500 dark:text-zinc-400">
                                    <th class="pb-2 font-medium">Product</th>
                                    <th class="pb-2 font-medium text-center">Qty</th>
                                    <th class="pb-2 font-medium text-right">Unit Price</th>
                                    <th class="pb-2 font-medium text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($order->orderLines as $line)
                                    <tr>
                                        <td class="py-2 text-zinc-900 dark:text-white">{{ $line->product?->name }}</td>
                                        <td class="py-2 text-center text-zinc-500 dark:text-zinc-400">{{ $line->quantity }}</td>
                                        <td class="py-2 text-right text-zinc-500 dark:text-zinc-400">${{ number_format($line->unit_price, 2) }}</td>
                                        <td class="py-2 text-right font-medium text-zinc-900 dark:text-white">${{ number_format($line->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <td colspan="3" class="pt-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Subtotal:</td>
                                    <td class="pt-2 text-right font-semibold text-zinc-900 dark:text-white">${{ number_format($order->subtotal, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400">No orders yet</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your Brand</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a brand profile to view orders.</p>
        </div>
    @endif
</div>
