@props([
    'showWordmark' => true,
    'showTagline' => false,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center']) }}>
    <x-hookline.logos.monogram class="h-12 w-12" />

    @if ($showWordmark)
        <span class="mt-3 text-xl font-semibold tracking-tight text-slate-900">{{ config('app.name', 'Hookline') }}</span>
    @endif

    @if ($showTagline)
        <span class="mt-1 text-sm text-slate-500">Durable webhook relay</span>
    @endif
</div>
