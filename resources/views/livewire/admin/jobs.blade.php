<?php

use App\Models\Job;
use App\Enums\JobStatus;
use App\Enums\JobType;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('Jobs - Admin')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $type = '';

    public function with(): array
    {
        $query = Job::with(['producer', 'venue', 'brand']);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('venue', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%");
                })->orWhereHas('producer', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%");
                });
            });
        }

        return [
            'jobs' => $query->latest('scheduled_start')->paginate(20),
            'stats' => [
                'total' => Job::count(),
                'scheduled' => Job::where('status', JobStatus::Scheduled)->count(),
                'inProgress' => Job::where('status', JobStatus::InProgress)->count(),
                'completed' => Job::where('status', JobStatus::Completed)->count(),
            ],
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Jobs</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Field operations and tastings</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['total'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Jobs</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['scheduled'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Scheduled</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['inProgress'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">In Progress</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['completed'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Completed</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-4">
        <div class="flex-1 max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search jobs..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="status" class="w-40">
            <option value="">All Statuses</option>
            @foreach(JobStatus::cases() as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="type" class="w-40">
            <option value="">All Types</option>
            @foreach(JobType::cases() as $typeOption)
                <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <!-- Jobs Table -->
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Type</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Venue</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Producer</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Brand</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">Scheduled</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($jobs as $job)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="p-2 rounded-lg bg-{{ $job->type->color() }}-100 dark:bg-{{ $job->type->color() }}-900/30">
                                    <flux:icon :name="$job->type->icon()" class="size-4 text-{{ $job->type->color() }}-600 dark:text-{{ $job->type->color() }}-400" />
                                </div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $job->type->label() }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">
                            {{ $job->venue?->name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $job->producer?->name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $job->brand?->name }}
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($job->scheduled_start)
                                {{ $job->scheduled_start->format('M j, Y g:i A') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" :color="$job->status->color()">
                                {{ $job->status->label() }}
                            </flux:badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No jobs found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $jobs->links() }}
    </div>
</div>
