<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('wholesale_price', 10, 2)->default(0);
            $table->decimal('rrp', 10, 2)->default(0);
            $table->integer('pack_size')->default(1);
            $table->integer('case_size')->default(1);
            $table->string('uom')->default('each'); // unit of measure
            $table->string('image_url')->nullable();
            $table->decimal('alcohol_percent', 5, 2)->nullable();
            $table->string('country_of_origin')->nullable();
            $table->string('region')->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('volume_ml', 10, 2)->nullable();
            $table->json('attributes')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->string('unleashed_guid')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'is_active']);
            $table->index(['category', 'subcategory']);
        });

        // Warehouses and stock levels
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('three_pl_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->default('AU');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('external_id')->nullable()->index();
            $table->string('unleashed_guid')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_on_order')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'quantity_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('products');
    }
};
