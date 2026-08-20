<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'Hookline') }}</title>
        @vite('resources/css/app.css')
    </head>
    <body class="min-h-full bg-zinc-950 text-zinc-100 antialiased">
        <div class="mx-auto max-w-2xl px-4 py-16 font-mono text-sm">
            {{ $slot }}
        </div>
    </body>
</html>
