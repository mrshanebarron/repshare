<?php

use App\Models\Producer;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Producers - Admin')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(): array
    {
        $query = Producer::withCount(['jobs', 'orders'])
            ->with('brands');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        return [
            'producers' => $query->latest()->paginate(20),
            'totalProducers' => Producer::count(),
            'activeProducers' => Producer::where('is_active', true)->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Producers</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ $activeProducers }} active of {{ $totalProducers }} total</p>
        </div>
    </div>

    <!-- Search -->
    <div class="max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search producers..." icon="magnifying-glass" />
    </div>

    <!-- Producers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($producers as $producer)
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $producer->name }}</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $producer->email }}</p>
                    </div>
                    @if($producer->is_active)
                        <flux:badge size="sm" color="emerald">Active</flux:badge>
                    @else
                        <flux:badge size="sm" color="zinc">Inactive</flux:badge>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $producer->completed_jobs_count ?? 0 }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Completed</p>
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $producer->orders_count }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Orders</p>
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-amber-600 dark:text-amber-400">{{ number_format($producer->rating ?? 0, 1) }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Rating</p>
                    </div>
                </div>

                @if($producer->brands->count() > 0)
                    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Represents:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($producer->brands->take(3) as $brand)
                                <flux:badge size="sm">{{ $brand->name }}</flux:badge>
                            @endforeach
                            @if($producer->brands->count() > 3)
                                <flux:badge size="sm" color="zinc">+{{ $producer->brands->count() - 3 }}</flux:badge>
                            @endif
                        </div>
                    </div>
                @endif

                @if($producer->phone)
                    <div class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $producer->phone }}
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                <p class="text-zinc-500 dark:text-zinc-400">No producers found</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $producers->links() }}
    </div>
</div>
