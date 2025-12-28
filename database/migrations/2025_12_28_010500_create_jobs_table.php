<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\JobType;
use App\Enums\JobStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default(JobType::SalesVisit->value);
            $table->string('status')->default(JobStatus::Scheduled->value);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->string('geoop_id')->nullable()->index();
            $table->json('brands')->nullable();
            $table->json('products')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['producer_id', 'scheduled_start']);
            $table->index(['venue_id', 'scheduled_start']);
            $table->index(['status', 'scheduled_start']);
        });

        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs_schedule')->cascadeOnDelete();
            $table->foreignId('producer_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->text('description')->nullable();
            $table->boolean('billable')->default(true);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->string('external_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('jobs_schedule');
    }
};
