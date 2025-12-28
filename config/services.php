<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Service Drivers
    |--------------------------------------------------------------------------
    |
    | Configure which service implementation to use for each integration.
    | Options: 'dummy' (local database) or the real service name.
    |
    | Set these in your .env file:
    | INVENTORY_DRIVER=dummy     (or 'unleashed' when ready)
    | JOBS_DRIVER=dummy          (or 'geoop' when ready)
    |
    */

    'inventory' => [
        'driver' => env('INVENTORY_DRIVER', 'dummy'),
    ],

    'jobs' => [
        'driver' => env('JOBS_DRIVER', 'dummy'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Unleashed Software (Inventory & Warehouse)
    |--------------------------------------------------------------------------
    |
    | Unleashed is the source of truth for inventory data.
    | Get API credentials from: https://app.unleashedsoftware.com
    |
    */

    'unleashed' => [
        'api_id' => env('UNLEASHED_API_ID'),
        'api_key' => env('UNLEASHED_API_KEY'),
        'base_url' => env('UNLEASHED_BASE_URL', 'https://api.unleashedsoftware.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GeoOp (Job Booking & Field Operations)
    |--------------------------------------------------------------------------
    |
    | GeoOp is the source of truth for field activity and job scheduling.
    | Get API credentials from: https://app.geoop.com
    |
    */

    'geoop' => [
        'api_key' => env('GEOOP_API_KEY'),
        'base_url' => env('GEOOP_BASE_URL', 'https://api.geoop.com/v1'),
        'webhook_secret' => env('GEOOP_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ALM Connect (Wholesale Ordering)
    |--------------------------------------------------------------------------
    |
    | ALM Connect integration for commercial ordering.
    |
    */

    'alm' => [
        'api_key' => env('ALM_API_KEY'),
        'base_url' => env('ALM_BASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Settings
    |--------------------------------------------------------------------------
    */

    'platform' => [
        'default_fee_percent' => env('PLATFORM_FEE_PERCENT', 5),
        'stock_reservation_minutes' => env('STOCK_RESERVATION_MINUTES', 30),
    ],

];
