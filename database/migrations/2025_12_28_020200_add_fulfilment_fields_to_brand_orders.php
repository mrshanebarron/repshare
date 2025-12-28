<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_orders', function (Blueprint $table) {
            $table->timestamp('picked_at')->nullable()->after('dispatched_at');
            $table->timestamp('packed_at')->nullable()->after('picked_at');
            $table->string('packer_name')->nullable()->after('packed_at');
            $table->text('packing_notes')->nullable()->after('packer_name');
            $table->string('carrier_service')->nullable()->after('carrier');
            $table->decimal('shipping_cost', 10, 2)->nullable()->after('carrier_service');
            $table->string('delivery_proof')->nullable()->after('delivered_at'); // Photo URL
            $table->string('signature_name')->nullable()->after('delivery_proof');
        });

        // Carrier lookup table
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('tracking_url_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('services')->nullable(); // Available service levels
            $table->timestamps();
        });

        // Seed common carriers
        \DB::table('carriers')->insert([
            ['code' => 'auspost', 'name' => 'Australia Post', 'tracking_url_template' => 'https://auspost.com.au/mypost/track/#/details/{tracking}', 'is_active' => true, 'services' => json_encode(['standard', 'express', 'parcel_post']), 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'startrack', 'name' => 'StarTrack', 'tracking_url_template' => 'https://startrack.com.au/track/{tracking}', 'is_active' => true, 'services' => json_encode(['express', 'premium']), 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'tnt', 'name' => 'TNT', 'tracking_url_template' => 'https://www.tnt.com/express/en_au/site/tracking.html?searchType=con&cons={tracking}', 'is_active' => true, 'services' => json_encode(['road', 'express', 'overnight']), 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'toll', 'name' => 'Toll', 'tracking_url_template' => 'https://online.toll.com.au/trackandtrace/Search?q={tracking}', 'is_active' => true, 'services' => json_encode(['ipec', 'priority']), 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'dhl', 'name' => 'DHL', 'tracking_url_template' => 'https://www.dhl.com/au-en/home/tracking/tracking-express.html?submit=1&tracking-id={tracking}', 'is_active' => true, 'services' => json_encode(['express', 'economy']), 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'couriers_please', 'name' => 'Couriers Please', 'tracking_url_template' => 'https://www.couriersplease.com.au/tools-track?cprefno={tracking}', 'is_active' => true, 'services' => json_encode(['standard', 'express']), 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'sendle', 'name' => 'Sendle', 'tracking_url_template' => 'https://track.sendle.com/{tracking}', 'is_active' => true, 'services' => json_encode(['standard', 'express']), 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'self', 'name' => 'Self Delivery', 'tracking_url_template' => null, 'is_active' => true, 'services' => json_encode(['local']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('carriers');

        Schema::table('brand_orders', function (Blueprint $table) {
            $table->dropColumn([
                'picked_at',
                'packed_at',
                'packer_name',
                'packing_notes',
                'carrier_service',
                'shipping_cost',
                'delivery_proof',
                'signature_name',
            ]);
        });
    }
};
