<?php

use App\Models\Job;
use App\Enums\JobStatus;
use App\Enums\JobType;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] #[Title('My Jobs')] class extends Component {
    use WithPagination;

    public string $status = '';
    public string $view = 'upcoming';

    public function with(): array
    {
        $producer = auth()->user()->producer;

        if (!$producer) {
            return ['producer' => null, 'jobs' => collect()];
        }

        $query = Job::where('producer_id', $producer->id)
            ->with(['venue', 'brand']);

        if ($this->view === 'upcoming') {
            $query->where('scheduled_start', '>=', now())
                ->orderBy('scheduled_start');
        } elseif ($this->view === 'past') {
            $query->where('scheduled_start', '<', now())
                ->orderByDesc('scheduled_start');
        } else {
            $query->orderByDesc('scheduled_start');
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return [
            'producer' => $producer,
            'jobs' => $query->paginate(15),
            'stats' => [
                'upcoming' => Job::where('producer_id', $producer->id)
                    ->where('scheduled_start', '>=', now())
                    ->count(),
                'today' => Job::where('producer_id', $producer->id)
                    ->whereDate('scheduled_start', today())
                    ->count(),
                'completed' => Job::where('producer_id', $producer->id)
                    ->where('status', JobStatus::Completed)
                    ->count(),
            ],
        ];
    }

    public function startJob(int $jobId): void
    {
        $job = Job::findOrFail($jobId);
        $job->update([
            'status' => JobStatus::InProgress,
            'actual_start' => now(),
        ]);
    }

    public function completeJob(int $jobId): void
    {
        $job = Job::findOrFail($jobId);
        $job->update([
            'status' => JobStatus::Completed,
            'actual_end' => now(),
        ]);

        // Update producer's completed jobs count
        $producer = $job->producer;
        if ($producer) {
            $producer->increment('completed_jobs_count');
        }
    }
}; ?>

<div class="space-y-6">
    @if($producer)
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">My Jobs</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Manage your schedule and assignments</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['upcoming'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Upcoming</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['today'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Today</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['completed'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Completed</p>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="flex gap-2">
            <button
                wire:click="$set('view', 'upcoming')"
                class="px-4 py-2 rounded-lg font-medium transition {{ $view === 'upcoming' ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}"
            >
                Upcoming
            </button>
            <button
                wire:click="$set('view', 'past')"
                class="px-4 py-2 rounded-lg font-medium transition {{ $view === 'past' ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}"
            >
                Past
            </button>
            <button
                wire:click="$set('view', 'all')"
                class="px-4 py-2 rounded-lg font-medium transition {{ $view === 'all' ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}"
            >
                All
            </button>
        </div>

        <!-- Jobs -->
        <div class="space-y-4">
            @forelse($jobs as $job)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-lg bg-{{ $job->type->color() }}-100 dark:bg-{{ $job->type->color() }}-900/30">
                                <flux:icon :name="$job->type->icon()" class="size-6 text-{{ $job->type->color() }}-600 dark:text-{{ $job->type->color() }}-400" />
                            </div>
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $job->venue?->name }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $job->type->label() }}
                                    @if($job->brand)
                                        | {{ $job->brand->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    {{ $job->scheduled_start?->format('M j, Y') }}
                                </p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $job->scheduled_start?->format('g:i A') }} - {{ $job->scheduled_end?->format('g:i A') }}
                                </p>
                            </div>
                            <flux:badge size="sm" :color="$job->status->color()">
                                {{ $job->status->label() }}
                            </flux:badge>
                        </div>
                    </div>
                    @if($job->status === JobStatus::Scheduled || $job->status === JobStatus::InProgress)
                        <div class="px-4 pb-4 flex gap-2 justify-end">
                            @if($job->status === JobStatus::Scheduled)
                                <flux:button wire:click="startJob({{ $job->id }})" size="sm" variant="primary">
                                    Start Job
                                </flux:button>
                            @elseif($job->status === JobStatus::InProgress)
                                <flux:button wire:click="completeJob({{ $job->id }})" size="sm" variant="primary">
                                    Complete Job
                                </flux:button>
                            @endif
                        </div>
                    @endif
                    @if($job->notes)
                        <div class="px-4 pb-4">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $job->notes }}</p>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400">No jobs found</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $jobs->links() }}
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your Profile</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a producer profile to view jobs.</p>
        </div>
    @endif
</div>
