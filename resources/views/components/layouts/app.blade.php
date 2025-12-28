<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'RepShare' }} - Drinks Network Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 antialiased">
    <flux:sidebar sticky stashable class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <flux:brand href="{{ route('dashboard') }}" logo="" name="RepShare" class="px-2 dark:text-white" />

        <flux:navlist variant="outline">
            @php $role = auth()->user()->getPrimaryRole(); @endphp

            @if($role === 'admin')
                <flux:navlist.item icon="home" href="{{ route('admin.dashboard') }}" :current="request()->routeIs('admin.dashboard')">Dashboard</flux:navlist.item>
                <flux:navlist.item icon="shopping-cart" href="{{ route('admin.orders') }}" :current="request()->routeIs('admin.orders')">Orders</flux:navlist.item>
                <flux:navlist.item icon="building-storefront" href="{{ route('admin.brands') }}" :current="request()->routeIs('admin.brands')">Brands</flux:navlist.item>
                <flux:navlist.item icon="map-pin" href="{{ route('admin.venues') }}" :current="request()->routeIs('admin.venues')">Venues</flux:navlist.item>
                <flux:navlist.item icon="user-group" href="{{ route('admin.producers') }}" :current="request()->routeIs('admin.producers')">Producers</flux:navlist.item>
                <flux:navlist.item icon="cube" href="{{ route('admin.products') }}" :current="request()->routeIs('admin.products')">Products</flux:navlist.item>
                <flux:navlist.item icon="calendar" href="{{ route('admin.jobs') }}" :current="request()->routeIs('admin.jobs')">Jobs</flux:navlist.item>
                <flux:navlist.item icon="chart-bar" href="{{ route('admin.analytics') }}" :current="request()->routeIs('admin.analytics')">Analytics</flux:navlist.item>
            @elseif($role === 'brand')
                <flux:navlist.item icon="home" href="{{ route('brand.dashboard') }}" :current="request()->routeIs('brand.dashboard')">Dashboard</flux:navlist.item>
                <flux:navlist.item icon="cube" href="{{ route('brand.products') }}" :current="request()->routeIs('brand.products')">Products</flux:navlist.item>
                <flux:navlist.item icon="shopping-cart" href="{{ route('brand.orders') }}" :current="request()->routeIs('brand.orders')">Orders</flux:navlist.item>
            @elseif($role === 'venue')
                <flux:navlist.item icon="home" href="{{ route('venue.dashboard') }}" :current="request()->routeIs('venue.dashboard')">Dashboard</flux:navlist.item>
                <flux:navlist.item icon="shopping-cart" href="{{ route('venue.orders') }}" :current="request()->routeIs('venue.orders')">Orders</flux:navlist.item>
                <flux:navlist.item icon="plus" href="{{ route('venue.orders.create') }}" :current="request()->routeIs('venue.orders.create')">New Order</flux:navlist.item>
            @elseif($role === 'producer')
                <flux:navlist.item icon="home" href="{{ route('producer.dashboard') }}" :current="request()->routeIs('producer.dashboard')">Dashboard</flux:navlist.item>
                <flux:navlist.item icon="calendar" href="{{ route('producer.jobs') }}" :current="request()->routeIs('producer.jobs')">Jobs</flux:navlist.item>
                <flux:navlist.item icon="shopping-cart" href="{{ route('producer.orders') }}" :current="request()->routeIs('producer.orders')">Orders</flux:navlist.item>
            @elseif($role === '3pl')
                <flux:navlist.item icon="home" href="{{ route('threePL.dashboard') }}" :current="request()->routeIs('threePL.dashboard')">Dashboard</flux:navlist.item>
                <flux:navlist.item icon="truck" href="{{ route('threePL.fulfilment') }}" :current="request()->routeIs('threePL.fulfilment')">Fulfilment</flux:navlist.item>
                <flux:navlist.item icon="cube" href="{{ route('threePL.inventory') }}" :current="request()->routeIs('threePL.inventory')">Inventory</flux:navlist.item>
            @endif
        </flux:navlist>

        <flux:spacer />

        <flux:navlist variant="outline">
            <flux:navlist.item icon="cog-6-tooth" href="#">Settings</flux:navlist.item>
        </flux:navlist>

        <flux:dropdown position="top" align="start" class="max-lg:hidden">
            <flux:profile name="{{ auth()->user()->name }}" class="cursor-pointer" />

            <flux:menu>
                <flux:menu.item icon="arrow-right-start-on-rectangle">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left">Logout</button>
                    </form>
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:profile name="{{ auth()->user()->name }}" size="xs" />
    </flux:header>

    <flux:main>
        {{ $slot }}
    </flux:main>

    @fluxScripts
</body>
</html>
