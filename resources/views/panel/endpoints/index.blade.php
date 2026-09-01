@use(App\Routing\WebRoute)

<div>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="hl-page-title">Endpoints</h1>
            <p class="hl-muted mt-1">Capture webhooks and forward them to your destinations.</p>
        </div>

        <button type="button" wire:click="openCreateModal" class="hl-btn-primary shrink-0">
            Create endpoint
        </button>
    </div>

    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="create-endpoint-title">
            <div class="hl-modal-backdrop" wire:click="closeCreateModal"></div>

            <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <h2 id="create-endpoint-title" class="hl-section-title">New endpoint</h2>
                    <button type="button" wire:click="closeCreateModal" class="text-slate-400 transition hover:text-slate-600" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="storeEndpoint" class="mt-4 space-y-4">
                    <div>
                        <label for="name" class="hl-label">Name</label>
                        <input
                            id="name"
                            type="text"
                            wire:model="form.name"
                            required
                            autofocus
                            class="hl-input"
                        >
                        @error('form.name')
                            <p class="hl-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="provider" class="hl-label">Provider</label>
                        <input
                            id="provider"
                            type="text"
                            wire:model="form.provider"
                            placeholder="Optional"
                            class="hl-input"
                        >
                        @error('form.provider')
                            <p class="hl-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeCreateModal" class="hl-btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" class="hl-btn-primary">
                            Create endpoint
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($endpoints->isEmpty())
        <p class="hl-muted mt-8">No endpoints yet. Create one to get started.</p>
    @else
        <ul class="mt-8 space-y-3">
            @foreach ($endpoints as $endpoint)
                <li class="hl-list-item">
                    <a href="{{ route(WebRoute::ShowEndpoints, $endpoint) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $endpoint->name }}</a>
                    @if ($endpoint->provider)
                        <span class="hl-muted"> · {{ $endpoint->provider }}</span>
                    @endif
                    <span class="hl-muted"> · {{ $endpoint->endpoint_events_count }} events</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
