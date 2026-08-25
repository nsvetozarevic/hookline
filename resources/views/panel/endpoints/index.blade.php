@use(App\Routing\WebRoute)

<div>
    <h1 class="text-xl text-zinc-50">Endpoints</h1>

    <form wire:submit="createEndpoint" class="mt-8 space-y-4">
        <div>
            <label for="name" class="block text-zinc-400">Name</label>
            <input
                id="name"
                type="text"
                wire:model="form.name"
                required
                autofocus
                class="mt-1 w-full border border-zinc-700 bg-zinc-900 px-3 py-2 text-zinc-50"
            >
            @error('form.name')
                <p class="mt-1 text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="provider" class="block text-zinc-400">Provider</label>
            <input
                id="provider"
                type="text"
                wire:model="form.provider"
                class="mt-1 w-full border border-zinc-700 bg-zinc-900 px-3 py-2 text-zinc-50"
            >
            @error('form.provider')
                <p class="mt-1 text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="border border-zinc-500 px-4 py-2 text-zinc-50 hover:bg-zinc-800">
            Create
        </button>
    </form>

    @if ($endpoints->isEmpty())
        <p class="mt-10 text-zinc-400">No endpoints yet. Create one above.</p>
    @else
        <ul class="mt-10 space-y-3">
            @foreach ($endpoints as $endpoint)
                <li class="border border-zinc-800 px-3 py-2">
                    <a href="{{ route(WebRoute::ShowEndpoints, $endpoint) }}" class="text-zinc-50 underline">{{ $endpoint->name }}</a>
                    @if ($endpoint->provider)
                        <span class="text-zinc-400"> · {{ $endpoint->provider }}</span>
                    @endif
                    <span class="text-zinc-500"> · {{ $endpoint->endpoint_events_count }} events</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
