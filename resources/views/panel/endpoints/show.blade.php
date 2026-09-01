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
    <nav class="hl-breadcrumb">
        <a href="{{ route(WebRoute::IndexEndpoints) }}">Endpoints</a>
        <span class="text-slate-300"> / </span>
        <span class="text-slate-700">{{ $endpoint->name }}</span>
    </nav>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <h1 class="hl-page-title">{{ $endpoint->name }}</h1>
        <span @class([
            'hl-badge',
            'hl-badge-succeeded' => $endpoint->is_active,
            'hl-badge-dead' => ! $endpoint->is_active,
        ])>{{ $endpoint->is_active ? 'Active' : 'Inactive' }}</span>
    </div>

    @if ($endpoint->provider)
        <p class="hl-muted mt-1">{{ $endpoint->provider }}</p>
    @endif

    <div class="hl-card mt-8 space-y-6">
        <div>
            <dt class="hl-label">Capture URL</dt>
            <dd class="mt-2 flex items-start justify-between gap-4">
                <code class="hl-code-inline">{{ $captureUrl }}</code>
                <button type="button" class="hl-btn-link shrink-0" x-on:click="copy('url', @js($captureUrl))" x-text="copied === 'url' ? 'Copied' : 'Copy'"></button>
            </dd>
        </div>

        <div>
            <dt class="hl-label">Capture token</dt>
            <dd class="mt-2 flex items-start justify-between gap-4">
                <code class="hl-code-inline">{{ $endpoint->capture_token }}</code>
                <button type="button" class="hl-btn-link shrink-0" x-on:click="copy('token', @js($endpoint->capture_token))" x-text="copied === 'token' ? 'Copied' : 'Copy'"></button>
            </dd>
        </div>

        <div>
            <dt class="hl-label">Signing secret</dt>
            <dd class="mt-2 flex items-start justify-between gap-4">
                <code class="hl-code-inline">{{ $currentSigningSecret->secret }}</code>
                <button type="button" class="hl-btn-link shrink-0" x-on:click="copy('secret', @js($currentSigningSecret->secret))" x-text="copied === 'secret' ? 'Copied' : 'Copy'"></button>
            </dd>
            <button type="button" wire:click="rotateSigningSecret" class="hl-btn-secondary mt-3">Rotate secret</button>
        </div>

        @foreach ($previousSigningSecrets as $previousSigningSecret)
            <div wire:key="previous-signing-secret-{{ $previousSigningSecret->id }}">
                <dt class="hl-label">Previous signing secret</dt>
                <dd class="mt-2 flex items-start justify-between gap-4">
                    <code class="hl-code-inline">{{ $previousSigningSecret->secret }}</code>
                    <button type="button" class="hl-btn-link shrink-0" x-on:click="copy('previous-{{ $previousSigningSecret->id }}', @js($previousSigningSecret->secret))" x-text="copied === 'previous-{{ $previousSigningSecret->id }}' ? 'Copied' : 'Copy'"></button>
                </dd>
                <p class="hl-muted mt-1">Expires {{ $previousSigningSecret->expires_at->toDateTimeString() }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        <h2 class="hl-section-title">Send a test</h2>
        <pre class="hl-code mt-3">curl -X POST {{ $captureUrl }} \
  -H 'content-type: application/json' \
  -H 'webhook-id: msg_demo' \
  -H 'webhook-timestamp: &lt;unix seconds&gt;' \
  -H 'webhook-signature: v1,&lt;base64 HMAC-SHA256 of id.timestamp.body&gt;' \
  -d '{"ok":true}'</pre>
        <p class="hl-muted mt-3">HMAC-SHA256 of webhook-id + "." + webhook-timestamp + "." + raw body, keyed with the decoded signing secret.</p>
    </div>

    <div class="hl-card mt-8">
        <div class="flex items-start justify-between gap-4">
            <h2 class="hl-section-title">Destinations</h2>
            <button type="button" wire:click="openAddDestinationModal" class="hl-btn-primary shrink-0">
                Add destination
            </button>
        </div>

        @if ($showAddDestinationModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="add-destination-title">
                <div class="hl-modal-backdrop" wire:click="closeAddDestinationModal"></div>

                <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                    <div class="flex items-start justify-between gap-4">
                        <h2 id="add-destination-title" class="hl-section-title">New destination</h2>
                        <button type="button" wire:click="closeAddDestinationModal" class="text-slate-400 transition hover:text-slate-600" aria-label="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="storeDestination" class="mt-4 space-y-4">
                        <div>
                            <label for="destination-url" class="hl-label">URL</label>
                            <input
                                id="destination-url"
                                type="url"
                                wire:model="form.url"
                                required
                                autofocus
                                placeholder="https://example.com/webhooks"
                                class="hl-input"
                            >
                            @error('form.url')
                                <p class="hl-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="closeAddDestinationModal" class="hl-btn-secondary">
                                Cancel
                            </button>
                            <button type="submit" class="hl-btn-primary">
                                Add destination
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if ($destinations->isEmpty())
            <p class="hl-muted mt-4">No destinations yet. Add one to fan out captured events.</p>
        @else
            <ul class="mt-4 space-y-2">
                @foreach ($destinations as $destination)
                    <li wire:key="destination-{{ $destination->id }}" class="hl-list-item text-sm">
                        <span class="font-mono text-slate-800 break-all">{{ $destination->url }}</span>
                        <span class="hl-muted"> · {{ $destination->is_active ? 'Active' : 'Inactive' }}</span>
                        <button
                            type="button"
                            wire:click="updateDestination({{ $destination->id }})"
                            class="hl-btn-link ml-2"
                        >
                            {{ $destination->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button
                            type="button"
                            wire:click="deleteDestination({{ $destination->id }})"
                            wire:confirm="Delete this destination?"
                            class="hl-btn-danger ml-2"
                        >
                            Delete
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-8">
        <h2 class="hl-section-title">Events</h2>
        @if ($endpointEvents->isEmpty())
            <p class="hl-muted mt-4">No events yet. Try the curl command above.</p>
        @else
            <ul class="mt-4 space-y-2">
                @foreach ($endpointEvents as $endpointEvent)
                    <li wire:key="endpoint-event-{{ $endpointEvent->id }}" class="hl-list-item text-sm">
                        <a href="{{ route(WebRoute::ShowEvents, $endpointEvent) }}" class="font-medium text-indigo-600 hover:text-indigo-700">#{{ $endpointEvent->id }}</a>
                        <span class="hl-muted" title="{{ $endpointEvent->deduplication_key }}"> · {{ Str::limit($endpointEvent->deduplication_key, 32) }}</span>
                        <span class="hl-muted"> · {{ $endpointEvent->headers['content-type'] ?? '-' }}</span>
                        <span class="hl-muted"> · {{ $endpointEvent->created_at->toDateTimeString() }}</span>
                        <span class="hl-muted"> · {{ strlen($endpointEvent->payload) }} B</span>
                    </li>
                @endforeach
            </ul>
            @if ($endpointEvents->hasPages())
                <div class="mt-4 flex gap-4">
                    @if (! $endpointEvents->onFirstPage())
                        <button type="button" wire:click="previousPage" class="hl-btn-link">Previous</button>
                    @endif
                    @if ($endpointEvents->hasMorePages())
                        <button type="button" wire:click="nextPage" class="hl-btn-link">Next</button>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>
