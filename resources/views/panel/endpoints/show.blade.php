@use(App\Routing\WebRoute)
@use(Illuminate\Support\Str)

<div
    x-data="{
        copied: null,
        copy(id, text) {
            navigator.clipboard.writeText(text)
            this.copied = id
            setTimeout(() => { if (this.copied === id) this.copied = null }, 2000)
        },
    }"
>
    <p class="text-zinc-400">
        <a href="{{ route(WebRoute::IndexEndpoints) }}" class="underline">Endpoints</a>
        <span class="text-zinc-600"> / </span>
        <span class="text-zinc-50">{{ $endpoint->name }}</span>
    </p>

    <h1 class="mt-4 text-xl text-zinc-50">{{ $endpoint->name }}</h1>
    <p class="mt-2 text-zinc-400">
        @if ($endpoint->provider)
            {{ $endpoint->provider }} ·
        @endif
        {{ $endpoint->is_active ? 'Active' : 'Inactive' }}
    </p>

    <dl class="mt-10 space-y-6">
        <div>
            <dt class="text-zinc-400">Capture URL</dt>
            <dd class="mt-1 flex items-start justify-between gap-4">
                <code class="break-all text-zinc-50">{{ $captureUrl }}</code>
                <button type="button" class="shrink-0 text-zinc-50 underline" x-on:click="copy('url', @js($captureUrl))" x-text="copied === 'url' ? 'Copied' : 'Copy'"></button>
            </dd>
        </div>

        <div>
            <dt class="text-zinc-400">Capture token</dt>
            <dd class="mt-1 flex items-start justify-between gap-4">
                <code class="break-all text-zinc-50">{{ $endpoint->capture_token }}</code>
                <button type="button" class="shrink-0 text-zinc-50 underline" x-on:click="copy('token', @js($endpoint->capture_token))" x-text="copied === 'token' ? 'Copied' : 'Copy'"></button>
            </dd>
        </div>

        <div>
            <dt class="text-zinc-400">Signing secret</dt>
            <dd class="mt-1 flex items-start justify-between gap-4">
                <code class="break-all text-zinc-50">{{ $currentSigningSecret->secret }}</code>
                <button type="button" class="shrink-0 text-zinc-50 underline" x-on:click="copy('secret', @js($currentSigningSecret->secret))" x-text="copied === 'secret' ? 'Copied' : 'Copy'"></button>
            </dd>
            <button type="button" wire:click="rotateSigningSecret" class="mt-2 border border-zinc-500 px-4 py-2 text-zinc-50 hover:bg-zinc-800">Rotate</button>
        </div>

        @foreach ($previousSigningSecrets as $previousSigningSecret)
            <div wire:key="previous-signing-secret-{{ $previousSigningSecret->id }}">
                <dt class="text-zinc-400">Previous signing secret</dt>
                <dd class="mt-1 flex items-start justify-between gap-4">
                    <code class="break-all text-zinc-50">{{ $previousSigningSecret->secret }}</code>
                    <button type="button" class="shrink-0 text-zinc-50 underline" x-on:click="copy('previous-{{ $previousSigningSecret->id }}', @js($previousSigningSecret->secret))" x-text="copied === 'previous-{{ $previousSigningSecret->id }}' ? 'Copied' : 'Copy'"></button>
                </dd>
                <p class="mt-1 text-zinc-400">Expires {{ $previousSigningSecret->expires_at->toDateTimeString() }}</p>
            </div>
        @endforeach
    </dl>

    <h2 class="mt-10 text-zinc-50">Send a test</h2>
    <pre class="mt-2 overflow-x-auto whitespace-pre-wrap border border-zinc-800 p-3 text-zinc-50">curl -X POST {{ $captureUrl }} \
  -H 'content-type: application/json' \
  -H 'webhook-id: msg_demo' \
  -H 'webhook-timestamp: &lt;unix seconds&gt;' \
  -H 'webhook-signature: v1,&lt;base64 HMAC-SHA256 of id.timestamp.body&gt;' \
  -d '{"ok":true}'</pre>
    <p class="mt-3 text-zinc-400">Compute the signature as in README.md: HMAC-SHA256 of webhook-id + "." + webhook-timestamp + "." + raw body, keyed with the decoded signing secret.</p>

    <h2 class="mt-10 text-zinc-50">Destinations</h2>
    <form wire:submit="storeDestination" class="mt-4 space-y-4">
        <div>
            <label for="destination-url" class="block text-zinc-400">URL</label>
            <input
                id="destination-url"
                type="url"
                wire:model="form.url"
                required
                placeholder="https://example.com/webhooks"
                class="mt-1 w-full border border-zinc-700 bg-zinc-900 px-3 py-2 text-zinc-50"
            >
            @error('form.url')
                <p class="mt-1 text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="border border-zinc-500 px-4 py-2 text-zinc-50 hover:bg-zinc-800">
            Add destination
        </button>
    </form>

    @if ($destinations->isEmpty())
        <p class="mt-4 text-zinc-400">No destinations yet — add one to fan out captured events.</p>
    @else
        <ul class="mt-4 space-y-2">
            @foreach ($destinations as $destination)
                <li wire:key="destination-{{ $destination->id }}" class="border border-zinc-800 px-3 py-2 text-zinc-400">
                    <span class="break-all text-zinc-50">{{ $destination->url }}</span>
                    <span> · {{ $destination->is_active ? 'Active' : 'Inactive' }}</span>
                    <button
                        type="button"
                        wire:click="updateDestination({{ $destination->id }})"
                        class="ml-2 text-zinc-50 underline"
                    >
                        {{ $destination->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button
                        type="button"
                        wire:click="deleteDestination({{ $destination->id }})"
                        wire:confirm="Delete this destination?"
                        class="ml-2 text-red-400 underline"
                    >
                        Delete
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    <h2 class="mt-10 text-zinc-50">Events</h2>
    @if ($endpointEvents->isEmpty())
        <p class="mt-4 text-zinc-400">No events yet — try the curl above.</p>
    @else
        <ul class="mt-4 space-y-2">
            @foreach ($endpointEvents as $endpointEvent)
                <li wire:key="endpoint-event-{{ $endpointEvent->id }}" class="border border-zinc-800 px-3 py-2 text-zinc-400">
                    <a href="{{ route(WebRoute::ShowEvents, $endpointEvent) }}" class="text-zinc-50 underline">#{{ $endpointEvent->id }}</a>
                    <span title="{{ $endpointEvent->deduplication_key }}"> · {{ Str::limit($endpointEvent->deduplication_key, 32) }}</span>
                    <span> · {{ $endpointEvent->headers['content-type'] ?? '—' }}</span>
                    <span> · {{ $endpointEvent->created_at->toDateTimeString() }}</span>
                    <span> · {{ strlen($endpointEvent->payload) }} B</span>
                </li>
            @endforeach
        </ul>
        @if ($endpointEvents->hasPages())
            <div class="mt-4 flex gap-4">
                @if (! $endpointEvents->onFirstPage())
                    <button type="button" wire:click="previousPage" class="text-zinc-50 underline">Previous</button>
                @endif
                @if ($endpointEvents->hasMorePages())
                    <button type="button" wire:click="nextPage" class="text-zinc-50 underline">Next</button>
                @endif
            </div>
        @endif
    @endif
</div>
