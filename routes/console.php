<?php

use Illuminate\Support\Facades\Schedule;

// Product and warehouse sync - every 5 minutes
Schedule::command('sync:unleashed-products')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sync:unleashed-warehouses')->hourly()->withoutOverlapping();

// Order updates from external systems - every 2 minutes
Schedule::command('sync:orders')->everyTwoMinutes()->withoutOverlapping();

// GeoOp job sync - every 5 minutes
Schedule::command('sync:geoop-jobs')->everyFiveMinutes()->withoutOverlapping();

// Process expired stock reservations - every 10 minutes
Schedule::command('reservations:process-expired')->everyTenMinutes()->withoutOverlapping();

// Clean up old activity logs - daily at 2am
Schedule::command('activitylog:clean')->dailyAt('02:00');
