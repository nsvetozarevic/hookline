<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'Hookline') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @vite('resources/css/app.css')
    </head>
    <body class="min-h-full bg-gradient-to-b from-slate-50 to-indigo-50/40 font-sans text-slate-900 antialiased">
        <div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
            <x-hookline.logo show-tagline />

            <main class="hl-card mt-8 w-full max-w-md">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
