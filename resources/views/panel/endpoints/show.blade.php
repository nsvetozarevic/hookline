@use(App\Routing\WebRoute)

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
                <code class="break-all text-zinc-50">{{ $endpoint->signing_secret }}</code>
                <button type="button" class="shrink-0 text-zinc-50 underline" x-on:click="copy('secret', @js($endpoint->signing_secret))" x-text="copied === 'secret' ? 'Copied' : 'Copy'"></button>
            </dd>
        </div>
    </dl>

    <h2 class="mt-10 text-zinc-50">Send a test</h2>
    <pre class="mt-2 overflow-x-auto whitespace-pre-wrap border border-zinc-800 p-3 text-zinc-50">curl -X POST {{ $captureUrl }} \
  -H 'Content-Type: application/json' \
  -H 'X-Hookline-Timestamp: &lt;unix seconds&gt;' \
  -H 'X-Hookline-Signature: HMAC-SHA256(timestamp + "." + raw body, signing_secret)' \
  -H 'X-Hookline-Event-Id: evt_demo' \
  -d '{"ok":true}'</pre>
    <p class="mt-3 text-zinc-400">Compute the signature as in README.md: HMAC-SHA256 of timestamp + "." + raw body, keyed with this signing secret.</p>
</div>
