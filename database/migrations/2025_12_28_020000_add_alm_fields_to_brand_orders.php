<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_orders', function (Blueprint $table) {
            $table->string('alm_status')->nullable()->after('alm_order_id');
            $table->string('alm_tracking_number')->nullable()->after('alm_status');
            $table->string('alm_carrier')->nullable()->after('alm_tracking_number');
            $table->timestamp('alm_submitted_at')->nullable()->after('alm_carrier');
            $table->timestamp('alm_shipped_at')->nullable()->after('alm_submitted_at');
        });

        // Add ALM supplier ID to brands
        Schema::table('brands', function (Blueprint $table) {
            $table->string('alm_supplier_id')->nullable()->after('external_id');
        });
    }

    public function down(): void
    {
        Schema::table('brand_orders', function (Blueprint $table) {
            $table->dropColumn([
                'alm_status',
                'alm_tracking_number',
                'alm_carrier',
                'alm_submitted_at',
                'alm_shipped_at',
            ]);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('alm_supplier_id');
        });
    }
};
