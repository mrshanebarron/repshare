<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Auth routes
Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('/register', 'auth.register')->name('register');
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard redirect based on role
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $role = $user->getPrimaryRole();

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'brand' => redirect()->route('brand.dashboard'),
            'venue' => redirect()->route('venue.dashboard'),
            'producer' => redirect()->route('producer.dashboard'),
            '3pl' => redirect()->route('threePL.dashboard'),
            default => redirect()->route('admin.dashboard'),
        };
    })->name('dashboard');

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Volt::route('/dashboard', 'admin.dashboard')->name('dashboard');
        Volt::route('/orders', 'admin.orders')->name('orders');
        Volt::route('/brands', 'admin.brands')->name('brands');
        Volt::route('/venues', 'admin.venues')->name('venues');
        Volt::route('/producers', 'admin.producers')->name('producers');
        Volt::route('/products', 'admin.products')->name('products');
        Volt::route('/jobs', 'admin.jobs')->name('jobs');
        Volt::route('/analytics', 'admin.analytics')->name('analytics');
    });

    // Brand routes
    Route::prefix('brand')->name('brand.')->group(function () {
        Volt::route('/dashboard', 'brand.dashboard')->name('dashboard');
        Volt::route('/products', 'brand.products')->name('products');
        Volt::route('/orders', 'brand.orders')->name('orders');
    });

    // Venue routes
    Route::prefix('venue')->name('venue.')->group(function () {
        Volt::route('/dashboard', 'venue.dashboard')->name('dashboard');
        Volt::route('/orders', 'venue.orders')->name('orders');
        Volt::route('/order/create', 'venue.create-order')->name('orders.create');
    });

    // Producer routes
    Route::prefix('producer')->name('producer.')->group(function () {
        Volt::route('/dashboard', 'producer.dashboard')->name('dashboard');
        Volt::route('/jobs', 'producer.jobs')->name('jobs');
        Volt::route('/orders', 'producer.orders')->name('orders');
    });

    // 3PL routes
    Route::prefix('3pl')->name('threePL.')->group(function () {
        Volt::route('/dashboard', 'threePL.dashboard')->name('dashboard');
        Volt::route('/fulfilment', 'threePL.fulfilment.index')->name('fulfilment');
        Volt::route('/inventory', 'threePL.inventory.index')->name('inventory');
    });
});
