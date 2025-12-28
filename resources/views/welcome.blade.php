<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RepShare - Drinks Network Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-900 text-white antialiased">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-zinc-900/80 backdrop-blur-lg border-b border-zinc-800">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-blue-600">
                    <span class="text-lg font-bold text-white">R</span>
                </div>
                <span class="text-xl font-bold">RepShare</span>
            </a>
            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 font-medium transition">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-zinc-300 hover:text-white transition">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 font-medium transition">
                        Get Started
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="pt-32 pb-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="max-w-3xl">
                <h1 class="text-5xl md:text-6xl font-bold leading-tight">
                    The
                    <span class="bg-gradient-to-r from-emerald-400 to-blue-500 bg-clip-text text-transparent">
                        Drinks Network
                    </span>
                    Marketplace
                </h1>
                <p class="mt-6 text-xl text-zinc-400 leading-relaxed">
                    RepShare connects brands, venues, producers, and 3PLs in one integrated platform.
                    Streamline ordering, field operations, inventory, and fulfilment.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('register') }}" class="px-6 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 font-medium transition text-lg">
                        Start Free Trial
                    </a>
                    <a href="{{ route('login') }}" class="px-6 py-3 rounded-lg border border-zinc-700 hover:border-zinc-600 font-medium transition text-lg">
                        View Demo
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-20 px-6 bg-zinc-800/50">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl font-bold text-center mb-12">Built for the Drinks Industry</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="p-6 rounded-xl bg-zinc-800 border border-zinc-700">
                    <div class="w-12 h-12 rounded-lg bg-emerald-600/20 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">For Brands</h3>
                    <p class="text-zinc-400">Manage products, track orders, and monitor sales performance across venues.</p>
                </div>

                <div class="p-6 rounded-xl bg-zinc-800 border border-zinc-700">
                    <div class="w-12 h-12 rounded-lg bg-blue-600/20 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">For Venues</h3>
                    <p class="text-zinc-400">One order, multiple suppliers. Automatic splitting, routing, and tracking.</p>
                </div>

                <div class="p-6 rounded-xl bg-zinc-800 border border-zinc-700">
                    <div class="w-12 h-12 rounded-lg bg-purple-600/20 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">For Producers</h3>
                    <p class="text-zinc-400">Schedule tastings, track visits, take orders on the spot, earn commissions.</p>
                </div>

                <div class="p-6 rounded-xl bg-zinc-800 border border-zinc-700">
                    <div class="w-12 h-12 rounded-lg bg-amber-600/20 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">For 3PLs</h3>
                    <p class="text-zinc-400">Real-time inventory, pick/pack/ship workflows, delivery tracking.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Integration -->
    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4">Powered by Industry-Leading Integrations</h2>
            <p class="text-zinc-400 mb-12 max-w-2xl mx-auto">
                RepShare orchestrates your existing systems. Unleashed for inventory, GeoOp for field ops, ALM Connect for wholesale.
            </p>
            <div class="flex flex-wrap justify-center gap-8">
                <div class="p-6 rounded-xl bg-zinc-800/50 border border-zinc-700">
                    <p class="font-semibold text-emerald-400">Unleashed</p>
                    <p class="text-sm text-zinc-500">Inventory & Warehousing</p>
                </div>
                <div class="p-6 rounded-xl bg-zinc-800/50 border border-zinc-700">
                    <p class="font-semibold text-blue-400">GeoOp</p>
                    <p class="text-sm text-zinc-500">Jobs & Field Ops</p>
                </div>
                <div class="p-6 rounded-xl bg-zinc-800/50 border border-zinc-700">
                    <p class="font-semibold text-purple-400">ALM Connect</p>
                    <p class="text-sm text-zinc-500">Wholesale Ordering</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-20 px-6 bg-gradient-to-br from-emerald-900/50 to-blue-900/50">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4">Ready to streamline your drinks business?</h2>
            <p class="text-zinc-400 mb-8">
                Join the platform that connects the entire drinks supply chain.
            </p>
            <a href="{{ route('register') }}" class="inline-block px-8 py-4 rounded-lg bg-white text-zinc-900 font-medium text-lg hover:bg-zinc-100 transition">
                Get Started Free
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 px-6 border-t border-zinc-800">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-blue-600">
                    <span class="text-sm font-bold text-white">R</span>
                </div>
                <span class="font-semibold">RepShare</span>
            </div>
            <p class="text-sm text-zinc-500">
                Built with Laravel + Livewire. Integrates with Unleashed + GeoOp.
            </p>
        </div>
    </footer>
</body>
</html>
