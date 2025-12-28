<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_records', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // producer_time, platform_fee, commission, subscription
            $table->foreignId('job_id')->nullable()->constrained('jobs_schedule')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('producer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->nullable(); // minutes, hours, units, etc.
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending'); // pending, invoiced, paid, cancelled
            $table->string('invoice_id')->nullable();
            $table->timestamp('invoiced_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['producer_id', 'status']);
            $table->index(['brand_id', 'status']);
            $table->index('created_at');
        });

        // Reviews table
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs_schedule')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_id')->constrained('users')->cascadeOnDelete();
            $table->string('reviewer_type'); // venue, producer, brand
            $table->string('reviewed_type'); // venue, producer, brand
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['job_id', 'reviewer_id', 'reviewed_id']);
            $table->index(['reviewed_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('billing_records');
    }
};
