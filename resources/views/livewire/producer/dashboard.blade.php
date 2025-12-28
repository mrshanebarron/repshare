<?php

use App\Models\Job;
use App\Models\Order;
use App\Enums\JobStatus;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Producer Dashboard')] class extends Component {
    public function with(): array
    {
        $producer = auth()->user()->producer;

        if (!$producer) {
            return ['producer' => null];
        }

        $todaysJobs = Job::where('producer_id', $producer->id)
            ->whereDate('scheduled_start', today())
            ->orderBy('scheduled_start')
            ->get();

        return [
            'producer' => $producer,
            'todaysJobs' => $todaysJobs,
            'upcomingJobs' => Job::where('producer_id', $producer->id)
                ->where('scheduled_start', '>', now())
                ->where('status', JobStatus::Scheduled)
                ->count(),
            'completedJobs' => $producer->completed_jobs_count,
            'rating' => $producer->rating,
            'totalOrders' => Order::where('producer_id', $producer->id)->count(),
            'brands' => $producer->brands,
        ];
    }
}; ?>

<div class="space-y-6">
    @if($producer)
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Welcome back, {{ $producer->name }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Here's your day at a glance</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $todaysJobs->count() }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Jobs Today</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $upcomingJobs }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Upcoming Jobs</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $completedJobs }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Completed Jobs</p>
            </div>
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($rating, 1) }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Rating</p>
            </div>
        </div>

        <!-- Today's Schedule -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="font-semibold text-zinc-900 dark:text-white">Today's Schedule</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($todaysJobs as $job)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="p-2 rounded-lg bg-{{ $job->type->color() }}-100 dark:bg-{{ $job->type->color() }}-900/30">
                                <flux:icon :name="$job->type->icon()" class="size-5 text-{{ $job->type->color() }}-600 dark:text-{{ $job->type->color() }}-400" />
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $job->venue?->name }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $job->type->label() }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $job->scheduled_start?->format('g:i A') }}</p>
                            <flux:badge size="sm" :color="$job->status->color()">{{ $job->status->label() }}</flux:badge>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">No jobs scheduled for today</div>
                @endforelse
            </div>
        </div>

        <!-- Brands -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <h2 class="font-semibold text-zinc-900 dark:text-white mb-4">Your Brands</h2>
            <div class="flex flex-wrap gap-2">
                @forelse($brands as $brand)
                    <flux:badge>{{ $brand->name }}</flux:badge>
                @empty
                    <p class="text-zinc-500 dark:text-zinc-400">No brands assigned yet</p>
                @endforelse
            </div>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-6">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Set Up Your Profile</h2>
            <p class="mt-2 text-amber-700 dark:text-amber-300">You need to create a producer profile to start taking jobs.</p>
        </div>
    @endif
</div>
