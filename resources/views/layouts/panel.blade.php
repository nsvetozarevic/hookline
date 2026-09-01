@use(App\Routing\WebRoute)

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
    <body class="min-h-full bg-slate-50 font-sans text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <a href="{{ route(WebRoute::IndexEndpoints) }}" class="flex items-center gap-2.5">
                    <x-hookline.logos.monogram class="h-8 w-8" />
                    <span class="text-lg font-semibold tracking-tight text-slate-900">{{ config('app.name', 'Hookline') }}</span>
                </a>

                @auth
                    <div class="flex items-center gap-4 text-sm">
                        <a href="{{ route(WebRoute::IndexEndpoints) }}" class="font-medium text-slate-600 hover:text-indigo-600">Endpoints</a>
                        <span class="hidden text-slate-400 sm:inline">{{ auth()->user()->email }}</span>
                        <form method="POST" action="{{ route(WebRoute::Logout) }}">
                            @csrf
                            <button type="submit" class="hl-btn-link">Log out</button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
            {{ $slot }}
        </main>
    </body>
</html>
