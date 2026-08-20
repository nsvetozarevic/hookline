@use(App\Routing\WebRoute)

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
            <header class="mb-10 flex items-center justify-between gap-4">
                <a href="{{ auth()->check() ? route(WebRoute::EndpointsIndex) : route(WebRoute::ShowLogin) }}" class="text-zinc-50">{{ config('app.name', 'Hookline') }}</a>
                @auth
                    <form method="POST" action="{{ route(WebRoute::Logout) }}" class="flex items-center gap-4">
                        @csrf
                        <span class="text-zinc-400">{{ auth()->user()->email }}</span>
                        <button type="submit" class="text-zinc-50 underline">Log out</button>
                    </form>
                @endauth
            </header>
            {{ $slot }}
        </div>
    </body>
</html>
