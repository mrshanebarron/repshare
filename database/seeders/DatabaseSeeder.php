<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Brand;
use App\Models\Venue;
use App\Models\Producer;
use App\Models\ThreePL;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Job;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\BrandOrder;
use App\Enums\JobType;
use App\Enums\JobStatus;
use App\Enums\OrderStatus;
use App\Enums\FulfilmentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->createRolesAndPermissions();
        $this->createUsers();
        $this->createThreePLsAndWarehouses();
        $this->createBrandsAndProducts();
        $this->createVenues();
        $this->createProducers();
        $this->createStockLevels();
        $this->createSampleOrders();
        $this->createSampleJobs();
    }

    private function createRolesAndPermissions(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $brandRole = Role::create(['name' => 'brand']);
        $venueRole = Role::create(['name' => 'venue']);
        $producerRole = Role::create(['name' => 'producer']);
        $threePlRole = Role::create(['name' => '3pl']);

        // Create permissions
        $permissions = [
            'view-dashboard',
            'manage-orders',
            'manage-products',
            'manage-inventory',
            'manage-jobs',
            'manage-users',
            'view-analytics',
            'manage-fulfilment',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions
        $adminRole->givePermissionTo(Permission::all());
        $brandRole->givePermissionTo(['view-dashboard', 'manage-products', 'view-analytics']);
        $venueRole->givePermissionTo(['view-dashboard', 'manage-orders']);
        $producerRole->givePermissionTo(['view-dashboard', 'manage-jobs', 'manage-orders']);
        $threePlRole->givePermissionTo(['view-dashboard', 'manage-fulfilment', 'manage-inventory']);
    }

    private function createUsers(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@repshare.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Brand users
        $brandUsers = [
            ['name' => 'James Wilson', 'email' => 'james@stonefish.com.au'],
            ['name' => 'Sarah Chen', 'email' => 'sarah@fourpillars.com.au'],
            ['name' => 'Michael Brown', 'email' => 'michael@archie.com.au'],
        ];

        foreach ($brandUsers as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('brand');
        }

        // Venue users
        $venueUsers = [
            ['name' => 'Emma Thompson', 'email' => 'emma@thetavern.com.au'],
            ['name' => 'David Kim', 'email' => 'david@rooftopbar.com.au'],
            ['name' => 'Sophie Martin', 'email' => 'sophie@coastalwines.com.au'],
        ];

        foreach ($venueUsers as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('venue');
        }

        // Producer users
        $producerUsers = [
            ['name' => 'Tom Anderson', 'email' => 'tom@salesrep.com.au'],
            ['name' => 'Lisa Park', 'email' => 'lisa@salesrep.com.au'],
        ];

        foreach ($producerUsers as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('producer');
        }

        // 3PL user
        $threePlUser = User::create([
            'name' => 'Warehouse Manager',
            'email' => 'warehouse@logistics.com.au',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $threePlUser->assignRole('3pl');
    }

    private function createThreePLsAndWarehouses(): void
    {
        $threePlUser = User::where('email', 'warehouse@logistics.com.au')->first();

        $threePL = ThreePL::create([
            'user_id' => $threePlUser->id,
            'name' => 'Sydney Logistics Co',
            'slug' => 'sydney-logistics',
            'code' => 'SYD-LOG',
            'description' => 'Full-service 3PL with temperature-controlled storage',
            'address' => '45 Industrial Drive',
            'city' => 'Alexandria',
            'state' => 'NSW',
            'postcode' => '2015',
            'country' => 'AU',
            'latitude' => -33.9065,
            'longitude' => 151.1945,
            'contact_name' => 'John Smith',
            'contact_email' => 'john@sydneylogistics.com.au',
            'contact_phone' => '02 9555 1234',
            'service_areas' => ['NSW', 'VIC', 'QLD'],
            'capabilities' => ['cold_storage', 'dangerous_goods', 'same_day'],
            'base_handling_fee' => 5.00,
            'per_case_fee' => 1.50,
        ]);

        $warehouse1 = Warehouse::create([
            'three_pl_id' => $threePL->id,
            'code' => 'SYD-MAIN',
            'name' => 'Sydney Main Warehouse',
            'address' => '45 Industrial Drive',
            'city' => 'Alexandria',
            'state' => 'NSW',
            'postcode' => '2015',
            'country' => 'AU',
            'latitude' => -33.9065,
            'longitude' => 151.1945,
        ]);

        $warehouse2 = Warehouse::create([
            'three_pl_id' => $threePL->id,
            'code' => 'MEL-MAIN',
            'name' => 'Melbourne Warehouse',
            'address' => '123 Port Road',
            'city' => 'Port Melbourne',
            'state' => 'VIC',
            'postcode' => '3207',
            'country' => 'AU',
            'latitude' => -37.8380,
            'longitude' => 144.9320,
        ]);
    }

    private function createBrandsAndProducts(): void
    {
        $brands = [
            [
                'user_email' => 'james@stonefish.com.au',
                'name' => 'Stonefish Wines',
                'slug' => 'stonefish-wines',
                'description' => 'Premium Australian wines from the Margaret River region',
                'country' => 'Australia',
                'region' => 'Margaret River',
                'categories' => ['wine', 'red_wine', 'white_wine'],
                'commission_rate' => 10,
                'platform_fee_percent' => 5,
                'products' => [
                    ['sku' => 'SF-CAB-2021', 'name' => 'Cabernet Sauvignon 2021', 'category' => 'wine', 'subcategory' => 'red', 'unit_price' => 28.00, 'case_size' => 6, 'alcohol_percent' => 14.5],
                    ['sku' => 'SF-SHZ-2020', 'name' => 'Shiraz Reserve 2020', 'category' => 'wine', 'subcategory' => 'red', 'unit_price' => 42.00, 'case_size' => 6, 'alcohol_percent' => 14.8],
                    ['sku' => 'SF-CHD-2022', 'name' => 'Chardonnay 2022', 'category' => 'wine', 'subcategory' => 'white', 'unit_price' => 24.00, 'case_size' => 6, 'alcohol_percent' => 13.0],
                    ['sku' => 'SF-SB-2023', 'name' => 'Sauvignon Blanc 2023', 'category' => 'wine', 'subcategory' => 'white', 'unit_price' => 22.00, 'case_size' => 6, 'alcohol_percent' => 12.5],
                ],
            ],
            [
                'user_email' => 'sarah@fourpillars.com.au',
                'name' => 'Four Pillars Distillery',
                'slug' => 'four-pillars',
                'description' => 'Award-winning Australian gin distillery',
                'country' => 'Australia',
                'region' => 'Yarra Valley',
                'categories' => ['spirits', 'gin'],
                'commission_rate' => 12,
                'platform_fee_percent' => 5,
                'products' => [
                    ['sku' => 'FP-RG-700', 'name' => 'Rare Dry Gin 700ml', 'category' => 'spirits', 'subcategory' => 'gin', 'unit_price' => 72.00, 'case_size' => 6, 'alcohol_percent' => 41.8],
                    ['sku' => 'FP-BG-700', 'name' => 'Bloody Shiraz Gin 700ml', 'category' => 'spirits', 'subcategory' => 'gin', 'unit_price' => 85.00, 'case_size' => 6, 'alcohol_percent' => 37.8],
                    ['sku' => 'FP-NG-700', 'name' => 'Navy Strength Gin 700ml', 'category' => 'spirits', 'subcategory' => 'gin', 'unit_price' => 82.00, 'case_size' => 6, 'alcohol_percent' => 58.8],
                ],
            ],
            [
                'user_email' => 'michael@archie.com.au',
                'name' => 'Archie Rose Distillery',
                'slug' => 'archie-rose',
                'description' => 'Sydney\'s first distillery since 1853',
                'country' => 'Australia',
                'region' => 'Sydney',
                'categories' => ['spirits', 'whisky', 'gin', 'vodka'],
                'commission_rate' => 11,
                'platform_fee_percent' => 5,
                'products' => [
                    ['sku' => 'AR-WM-700', 'name' => 'White Rye Malt Whisky 700ml', 'category' => 'spirits', 'subcategory' => 'whisky', 'unit_price' => 99.00, 'case_size' => 6, 'alcohol_percent' => 46.0],
                    ['sku' => 'AR-SG-700', 'name' => 'Signature Dry Gin 700ml', 'category' => 'spirits', 'subcategory' => 'gin', 'unit_price' => 78.00, 'case_size' => 6, 'alcohol_percent' => 42.0],
                    ['sku' => 'AR-VK-700', 'name' => 'Original Vodka 700ml', 'category' => 'spirits', 'subcategory' => 'vodka', 'unit_price' => 65.00, 'case_size' => 6, 'alcohol_percent' => 40.0],
                ],
            ],
        ];

        foreach ($brands as $brandData) {
            $user = User::where('email', $brandData['user_email'])->first();
            $products = $brandData['products'];
            unset($brandData['products'], $brandData['user_email']);

            $brand = Brand::create([
                'user_id' => $user->id,
                ...$brandData,
            ]);

            foreach ($products as $productData) {
                Product::create([
                    'brand_id' => $brand->id,
                    'slug' => Str::slug($productData['name']),
                    'pack_size' => 1,
                    'wholesale_price' => $productData['unit_price'] * 0.6,
                    'rrp' => $productData['unit_price'] * 1.5,
                    ...$productData,
                ]);
            }
        }
    }

    private function createVenues(): void
    {
        $venues = [
            [
                'user_email' => 'emma@thetavern.com.au',
                'name' => 'The Tavern',
                'slug' => 'the-tavern',
                'type' => 'bar',
                'description' => 'Inner city cocktail bar and restaurant',
                'address' => '123 Crown Street',
                'city' => 'Surry Hills',
                'state' => 'NSW',
                'postcode' => '2010',
                'latitude' => -33.8847,
                'longitude' => 151.2132,
                'liquor_license' => 'LIQP770000123',
                'trading_hours' => [
                    'mon' => ['open' => '16:00', 'close' => '00:00'],
                    'tue' => ['open' => '16:00', 'close' => '00:00'],
                    'wed' => ['open' => '16:00', 'close' => '00:00'],
                    'thu' => ['open' => '16:00', 'close' => '02:00'],
                    'fri' => ['open' => '16:00', 'close' => '02:00'],
                    'sat' => ['open' => '14:00', 'close' => '02:00'],
                    'sun' => ['open' => '14:00', 'close' => '22:00'],
                ],
            ],
            [
                'user_email' => 'david@rooftopbar.com.au',
                'name' => 'Rooftop Bar & Grill',
                'slug' => 'rooftop-bar-grill',
                'type' => 'restaurant',
                'description' => 'Modern Australian rooftop dining',
                'address' => '88 George Street',
                'city' => 'Sydney',
                'state' => 'NSW',
                'postcode' => '2000',
                'latitude' => -33.8617,
                'longitude' => 151.2087,
                'liquor_license' => 'LIQP770000456',
            ],
            [
                'user_email' => 'sophie@coastalwines.com.au',
                'name' => 'Coastal Wine Bar',
                'slug' => 'coastal-wine-bar',
                'type' => 'wine_bar',
                'description' => 'Relaxed beachside wine bar',
                'address' => '42 The Esplanade',
                'city' => 'Bondi Beach',
                'state' => 'NSW',
                'postcode' => '2026',
                'latitude' => -33.8908,
                'longitude' => 151.2743,
                'liquor_license' => 'LIQP770000789',
            ],
        ];

        foreach ($venues as $venueData) {
            $user = User::where('email', $venueData['user_email'])->first();
            unset($venueData['user_email']);

            Venue::create([
                'user_id' => $user->id,
                'contact_name' => $user->name,
                'contact_email' => $user->email,
                ...$venueData,
            ]);
        }
    }

    private function createProducers(): void
    {
        $producers = [
            [
                'user_email' => 'tom@salesrep.com.au',
                'name' => 'Tom Anderson',
                'slug' => 'tom-anderson',
                'bio' => 'Experienced wine and spirits sales rep with 10+ years in the industry',
                'phone' => '0412 345 678',
                'city' => 'Sydney',
                'state' => 'NSW',
                'hourly_rate' => 45.00,
                'commission_percent' => 8,
                'service_areas' => ['Sydney CBD', 'Eastern Suburbs', 'Inner West'],
                'certifications' => ['WSET Level 3', 'RSA'],
                'max_jobs_per_day' => 6,
                'rating' => 4.8,
                'completed_jobs_count' => 156,
            ],
            [
                'user_email' => 'lisa@salesrep.com.au',
                'name' => 'Lisa Park',
                'slug' => 'lisa-park',
                'bio' => 'Passionate about craft spirits and connecting producers with venues',
                'phone' => '0423 456 789',
                'city' => 'Sydney',
                'state' => 'NSW',
                'hourly_rate' => 42.00,
                'commission_percent' => 7,
                'service_areas' => ['North Shore', 'Northern Beaches'],
                'certifications' => ['WSET Level 2', 'RSA'],
                'max_jobs_per_day' => 5,
                'rating' => 4.9,
                'completed_jobs_count' => 98,
            ],
        ];

        $brands = Brand::all();

        foreach ($producers as $producerData) {
            $user = User::where('email', $producerData['user_email'])->first();
            unset($producerData['user_email']);

            $producer = Producer::create([
                'user_id' => $user->id,
                ...$producerData,
            ]);

            // Attach some brands
            $producer->brands()->attach(
                $brands->random(2)->pluck('id'),
                ['is_primary' => false, 'started_at' => now()->subMonths(rand(1, 12))]
            );
        }
    }

    private function createStockLevels(): void
    {
        $warehouses = Warehouse::all();
        $products = Product::all();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                $onHand = rand(20, 200);
                StockLevel::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity_on_hand' => $onHand,
                    'quantity_reserved' => rand(0, 10),
                    'quantity_available' => $onHand - rand(0, 10),
                    'quantity_on_order' => rand(0, 50),
                    'reorder_point' => 20,
                    'reorder_quantity' => 48,
                    'last_synced_at' => now(),
                ]);
            }
        }
    }

    private function createSampleOrders(): void
    {
        $venues = Venue::all();
        $producers = Producer::all();
        $products = Product::all();
        $warehouses = Warehouse::all();

        // Create 5 sample orders
        for ($i = 0; $i < 5; $i++) {
            $venue = $venues->random();
            $producer = $producers->random();
            $orderProducts = $products->random(rand(2, 4));

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'venue_id' => $venue->id,
                'producer_id' => $producer->id,
                'status' => collect(OrderStatus::cases())->random(),
                'delivery_address' => $venue->address,
                'delivery_city' => $venue->city,
                'delivery_state' => $venue->state,
                'delivery_postcode' => $venue->postcode,
                'requested_delivery_date' => now()->addDays(rand(1, 7)),
            ]);

            $subtotal = 0;
            $linesByBrand = [];

            foreach ($orderProducts as $product) {
                $quantity = rand(1, 6) * $product->case_size;
                $lineTotal = $quantity * $product->unit_price;
                $subtotal += $lineTotal;

                $line = OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->unit_price,
                    'line_total' => $lineTotal,
                ]);

                $linesByBrand[$product->brand_id][] = $line;
            }

            // Create brand orders
            foreach ($linesByBrand as $brandId => $lines) {
                $brand = Brand::find($brandId);
                $warehouse = $warehouses->random();

                $brandSubtotal = collect($lines)->sum('line_total');
                $commission = $brandSubtotal * ($brand->commission_rate / 100);
                $platformFee = $brandSubtotal * ($brand->platform_fee_percent / 100);

                $brandOrder = BrandOrder::create([
                    'order_number' => 'BRD-' . strtoupper(Str::random(8)),
                    'order_id' => $order->id,
                    'brand_id' => $brandId,
                    'warehouse_id' => $warehouse->id,
                    'three_pl_id' => $warehouse->three_pl_id,
                    'status' => $order->status,
                    'fulfilment_status' => $order->status === OrderStatus::Delivered ? FulfilmentStatus::Delivered : FulfilmentStatus::Pending,
                    'subtotal' => $brandSubtotal,
                    'commission_amount' => $commission,
                    'platform_fee' => $platformFee,
                    'grand_total' => $brandSubtotal,
                    'net_to_brand' => $brandSubtotal - $commission - $platformFee,
                ]);

                foreach ($lines as $line) {
                    $line->update(['brand_order_id' => $brandOrder->id]);
                }
            }

            $order->update([
                'subtotal' => $subtotal,
                'platform_fee' => $subtotal * 0.05,
                'grand_total' => $subtotal,
            ]);
        }
    }

    private function createSampleJobs(): void
    {
        $producers = Producer::with('brands')->get();
        $venues = Venue::all();

        foreach ($producers as $producer) {
            // Create jobs for next 2 weeks
            for ($day = 0; $day < 14; $day++) {
                if (rand(0, 1) === 0) continue; // Skip some days

                $jobCount = rand(1, 3);
                for ($j = 0; $j < $jobCount; $j++) {
                    $venue = $venues->random();
                    $type = collect(JobType::cases())->random();
                    $scheduledStart = now()->addDays($day)->setHour(rand(9, 15))->setMinute(0);
                    $duration = rand(30, 120);
                    $status = $day < 0 ? JobStatus::Completed : ($day === 0 ? collect([JobStatus::Scheduled, JobStatus::InProgress])->random() : JobStatus::Scheduled);

                    Job::create([
                        'producer_id' => $producer->id,
                        'venue_id' => $venue->id,
                        'type' => $type,
                        'status' => $status,
                        'title' => $type->label() . ' at ' . $venue->name,
                        'scheduled_start' => $scheduledStart,
                        'scheduled_end' => $scheduledStart->copy()->addMinutes($duration),
                        'actual_start' => $status === JobStatus::Completed ? $scheduledStart : null,
                        'actual_end' => $status === JobStatus::Completed ? $scheduledStart->copy()->addMinutes($duration) : null,
                        'duration_minutes' => $status === JobStatus::Completed ? $duration : null,
                        'brands' => $producer->brands->pluck('id')->toArray(),
                    ]);
                }
            }
        }
    }
}
