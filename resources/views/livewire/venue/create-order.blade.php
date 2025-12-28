<?php

use App\Models\Product;
use App\Models\Order;
use App\Actions\Orders\CreateOrderAction;
use App\Actions\Orders\SplitOrderByBrandAction;
use App\Data\OrderLineData;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Create Order')] class extends Component {
    public array $cart = [];
    public string $search = '';
    public string $notes = '';

    public function with(): array
    {
        $venue = auth()->user()->venue;

        if (!$venue) {
            return ['venue' => null, 'products' => collect()];
        }

        $query = Product::where('is_active', true)
            ->with(['brand', 'stockLevels']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%")
                    ->orWhereHas('brand', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        return [
            'venue' => $venue,
            'products' => $query->orderBy('name')->get(),
            'cartTotal' => $this->getCartTotal(),
            'cartCount' => array_sum($this->cart),
        ];
    }

    public function addToCart(int $productId): void
    {
        if (!isset($this->cart[$productId])) {
            $this->cart[$productId] = 0;
        }
        $this->cart[$productId]++;
    }

    public function removeFromCart(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]--;
            if ($this->cart[$productId] <= 0) {
                unset($this->cart[$productId]);
            }
        }
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            unset($this->cart[$productId]);
        } else {
            $this->cart[$productId] = $quantity;
        }
    }

    public function getCartTotal(): float
    {
        $total = 0;
        foreach ($this->cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $total += $product->wholesale_price * $quantity;
            }
        }
        return $total;
    }

    public function placeOrder(): void
    {
        $venue = auth()->user()->venue;

        if (!$venue || empty($this->cart)) {
            return;
        }

        $lines = [];
        foreach ($this->cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $lines[] = new OrderLineData(
                    productId: $product->id,
                    quantity: $quantity,
                    unitPrice: $product->wholesale_price,
                    discount: 0,
                );
            }
        }

        $order = app(CreateOrderAction::class)->execute(
            venueId: $venue->id,
            lines: $lines,
            notes: $this->notes,
        );

        app(SplitOrderByBrandAction::class)->execute($order);

        session()->flash('success', 'Order placed successfully!');
        $this->redirect(route('venue.orders'));
    }
}; ?>

<div class="space-y-6">
    @if($venue)
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Create Order</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Browse products and add to your order</p>
            </div>
            <flux:button href="{{ route('venue.orders') }}" variant="ghost" icon="arrow-left">
                Back to Orders
            </flux:button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Products -->
            <div class="lg:col-span-2 space-y-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($products as $product)
                        @php
                            $inCart = $cart[$product->id] ?? 0;
                            $stock = $product->stockLevels->sum('quantity_available');
                        @endphp
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 {{ $inCart > 0 ? 'ring-2 ring-emerald-500' : '' }}">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $product->name }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $product->brand?->name }}</p>
                                </div>
                                <p class="font-semibold text-zinc-900 dark:text-white">${{ number_format($product->wholesale_price, 2) }}</p>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $product->unit_size }} |
                                    <span class="{{ $stock > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $stock > 0 ? $stock . ' in stock' : 'Out of stock' }}
                                    </span>
                                </div>
                                @if($inCart > 0)
                                    <div class="flex items-center gap-2">
                                        <button wire:click="removeFromCart({{ $product->id }})" class="p-1 rounded bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600">
                                            <flux:icon name="minus" class="size-4" />
                                        </button>
                                        <span class="w-8 text-center font-medium">{{ $inCart }}</span>
                                        <button wire:click="addToCart({{ $product->id }})" class="p-1 rounded bg-zinc-200 dark:bg-zinc-700 hover:bg-zinc-300 dark:hover:bg-zinc-600">
                                            <flux:icon name="plus" class="size-4" />
                                        </button>
                                    </div>
                                @else
                                    <flux:button wire:click="addToCart({{ $product->id }})" size="sm" variant="primary" :disabled="$stock === 0">
                                        Add
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full p-8 text-center text-zinc-500 dark:text-zinc-400">
                            No products found
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Cart -->
            <div class="lg:col-span-1">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 sticky top-24">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">Your Order</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $cartCount }} items</p>
                    </div>

                    @if(count($cart) > 0)
                        <div class="p-4 space-y-3 max-h-64 overflow-y-auto">
                            @foreach($cart as $productId => $quantity)
                                @php $product = \App\Models\Product::find($productId); @endphp
                                @if($product)
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $product->name }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">${{ number_format($product->wholesale_price, 2) }} x {{ $quantity }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-sm">${{ number_format($product->wholesale_price * $quantity, 2) }}</span>
                                            <button wire:click="removeFromCart({{ $productId }})" class="text-red-500 hover:text-red-700">
                                                <flux:icon name="x-mark" class="size-4" />
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                            <flux:textarea wire:model="notes" placeholder="Order notes (optional)..." rows="2" />
                        </div>

                        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="flex justify-between items-center mb-4">
                                <span class="font-semibold text-zinc-900 dark:text-white">Total</span>
                                <span class="text-xl font-bold text-zinc-900 dark:text-white">${{ number_format($cartTotal, 2) }}</span>
                            </div>
                            <flux:button wire:click="placeOrder" variant="primary" class="w-full">
                                Place Order
                            </flux:button>
                        </div>
                    @else
                        <div class="p-8 text-center text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="shopping-cart" class="size-12 mx-auto mb-2 opacity-50" />
                            <p>Your cart is empty</p>
                            <p class="text-sm">Add products to get started</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your Venue</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a venue profile to place orders.</p>
        </div>
    @endif
</div>
