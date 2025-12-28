<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->json('service_areas')->nullable();
            $table->json('certifications')->nullable();
            $table->json('availability')->nullable();
            $table->integer('max_jobs_per_day')->default(8);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('completed_jobs_count')->default(0);
            $table->string('external_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Pivot table for producer <-> brand relationships
        Schema::create('brand_producer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('producer_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->date('started_at')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'producer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_producer');
        Schema::dropIfExists('producers');
    }
};
