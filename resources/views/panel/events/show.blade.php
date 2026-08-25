@use(App\Routing\WebRoute)

<div>
    <p class="text-zinc-400">
        <a href="{{ route(WebRoute::IndexEndpoints) }}" class="underline">Endpoints</a>
        <span class="text-zinc-600"> / </span>
        <a href="{{ route(WebRoute::ShowEndpoints, $endpointEvent->endpoint) }}" class="underline">{{ $endpointEvent->endpoint->name }}</a>
        <span class="text-zinc-600"> / </span>
        <span class="text-zinc-50">#{{ $endpointEvent->id }}</span>
    </p>

    <h1 class="mt-4 text-xl text-zinc-50">Event #{{ $endpointEvent->id }}</h1>
    <p class="mt-2 text-zinc-400">
        <span title="{{ $endpointEvent->deduplication_key }}">{{ $endpointEvent->deduplication_key }}</span>
        <span> · {{ $endpointEvent->created_at->toDateTimeString() }}</span>
    </p>

    <h2 class="mt-10 text-zinc-50">Headers</h2>
    @if ($endpointEvent->headers === [])
        <p class="mt-4 text-zinc-400">No headers.</p>
    @else
        <table class="mt-4 w-full border-collapse text-left">
            <tbody>
                @foreach ($endpointEvent->headers as $name => $value)
                    <tr class="border-b border-zinc-800">
                        <th class="py-2 pr-4 font-normal text-zinc-400">{{ $name }}</th>
                        <td class="py-2 text-zinc-50">{{ $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="mt-10 text-zinc-50">Payload</h2>
    <pre class="mt-4 overflow-x-auto whitespace-pre-wrap border border-zinc-800 p-3 text-zinc-50">{{ $payloadForDisplay }}</pre>
    @if ($payloadIsTruncated)
        <p class="mt-3 text-zinc-400">Truncated, {{ (int) ceil(strlen($endpointEvent->payload) / 1024) }} KB total.</p>
    @endif
</div>
