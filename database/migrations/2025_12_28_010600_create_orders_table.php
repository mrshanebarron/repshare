<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\OrderStatus;
use App\Enums\FulfilmentStatus;

return new class extends Migration
{
    public function up(): void
    {
        // Master order (the original order before splitting)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('producer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('jobs_schedule')->nullOnDelete();
            $table->string('status')->default(OrderStatus::Draft->value);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_state')->nullable();
            $table->string('delivery_postcode')->nullable();
            $table->date('requested_delivery_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['venue_id', 'status']);
            $table->index(['producer_id', 'created_at']);
            $table->index('status');
        });

        // Split orders (one per brand from the master order)
        Schema::create('brand_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('three_pl_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default(OrderStatus::Pending->value);
            $table->string('fulfilment_status')->default(FulfilmentStatus::Pending->value);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('net_to_brand', 12, 2)->default(0);
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->string('unleashed_order_id')->nullable()->index();
            $table->string('alm_order_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'status']);
            $table->index(['order_id', 'brand_id']);
            $table->index('fulfilment_status');
        });

        // Order lines
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku');
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });

        // Stock reservations
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('status')->default('reserved'); // reserved, committed, released
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['product_id', 'warehouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('brand_orders');
        Schema::dropIfExists('orders');
    }
};
