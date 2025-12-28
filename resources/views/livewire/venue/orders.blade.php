<?php

use App\Models\Order;
use App\Enums\OrderStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('My Orders')] class extends Component {
    use WithPagination;

    public string $status = '';

    public function with(): array
    {
        $venue = auth()->user()->venue;

        if (!$venue) {
            return ['orders' => collect(), 'venue' => null];
        }

        $query = Order::where('venue_id', $venue->id)
            ->with(['brandOrders.brand', 'producer']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return [
            'venue' => $venue,
            'orders' => $query->latest()->paginate(15),
        ];
    }
}; ?>

<div class="space-y-6">
    @if($venue)
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Orders</h1>
                <p class="text-zinc-500 dark:text-zinc-400">View and track your orders</p>
            </div>
            <flux:button href="{{ route('venue.orders.create') }}" variant="primary" icon="plus">
                New Order
            </flux:button>
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
                                {{ $order->created_at->format('M j, Y g:i A') }}
                                @if($order->producer)
                                    | via {{ $order->producer->name }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <p class="text-lg font-semibold text-zinc-900 dark:text-white">${{ number_format($order->grand_total, 2) }}</p>
                            <flux:badge size="sm" :color="$order->status->color()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </div>
                    </div>
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">Suppliers:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($order->brandOrders as $brandOrder)
                                <div class="px-3 py-1 rounded-lg bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 text-sm">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $brandOrder->brand?->name }}</span>
                                    <span class="text-zinc-500 dark:text-zinc-400"> - ${{ number_format($brandOrder->subtotal, 2) }}</span>
                                    <flux:badge size="sm" :color="$brandOrder->fulfilment_status->color()" class="ml-2">
                                        {{ $brandOrder->fulfilment_status->label() }}
                                    </flux:badge>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400 mb-4">No orders yet</p>
                    <flux:button href="{{ route('venue.orders.create') }}" variant="primary">
                        Place Your First Order
                    </flux:button>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your Venue</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a venue profile to place orders.</p>
        </div>
    @endif
</div>
