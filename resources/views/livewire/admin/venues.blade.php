<?php

use App\Models\Venue;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Venues - Admin')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(): array
    {
        $query = Venue::withCount('orders')
            ->withSum('orders', 'grand_total');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('suburb', 'like', "%{$this->search}%");
            });
        }

        return [
            'venues' => $query->latest()->paginate(20),
            'totalVenues' => Venue::count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Venues</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ $totalVenues }} venues registered</p>
        </div>
    </div>

    <!-- Search -->
    <div class="max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search venues..." icon="magnifying-glass" />
    </div>

    <!-- Venues Table -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Venue</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Type</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Location</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Orders</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Spent</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($venues as $venue)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $venue->name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $venue->email }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ ucfirst($venue->type ?? '-') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $venue->suburb }}, {{ $venue->state }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center font-medium text-zinc-900 dark:text-white">
                            {{ $venue->orders_count }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-zinc-900 dark:text-white">
                            ${{ number_format($venue->orders_sum_grand_total ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($venue->is_active)
                                <flux:badge size="sm" color="emerald">Active</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Inactive</flux:badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No venues found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $venues->links() }}
    </div>
</div>
