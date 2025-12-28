<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} - RepShare</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-900 antialiased">
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-600/20 to-blue-600/20 pointer-events-none"></div>
        <div class="relative flex w-full max-w-md flex-col gap-6">
            <a href="{{ route('home') }}" class="flex items-center justify-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-blue-600">
                    <span class="text-lg font-bold text-white">R</span>
                </div>
                <span class="text-2xl font-bold text-white">RepShare</span>
            </a>
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900/80 backdrop-blur-sm p-8">
                {{ $slot }}
            </div>
        </div>
    </div>
    @fluxScripts
</body>
</html>
