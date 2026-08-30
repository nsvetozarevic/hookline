@use(App\Routing\WebRoute)
@use(Illuminate\Support\Str)

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

    <h2 class="mt-10 text-zinc-50">Deliveries</h2>
    @if ($deliveries->isEmpty())
        <p class="mt-4 text-zinc-400">No deliveries.</p>
    @else
        <table class="mt-4 w-full border-collapse text-left">
            <thead>
                <tr class="border-b border-zinc-800 text-zinc-400">
                    <th class="py-2 pr-4 font-normal">Destination</th>
                    <th class="py-2 pr-4 font-normal">Status</th>
                    <th class="py-2 pr-4 font-normal">Attempts</th>
                    <th class="py-2 pr-4 font-normal">Last response</th>
                    <th class="py-2 pr-4 font-normal">Last result</th>
                    <th class="py-2 pr-4 font-normal">Last error</th>
                    <th class="py-2 font-normal"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveries as $delivery)
                    <tr class="border-b border-zinc-800" wire:key="delivery-{{ $delivery->id }}">
                        <td class="py-2 pr-4 text-zinc-50">{{ $delivery->destination->url }}</td>
                        <td class="py-2 pr-4 text-zinc-50">{{ $delivery->status->value }}</td>
                        <td class="py-2 pr-4 text-zinc-50">{{ $delivery->attempts }}</td>
                        <td class="py-2 pr-4 text-zinc-50">{{ $delivery->last_status_code ?? '—' }}</td>
                        <td class="py-2 pr-4 text-zinc-50">{{ $delivery->latestDeliveryAttempt?->result->value ?? '—' }}</td>
                        <td class="py-2 pr-4 text-zinc-50" @if ($delivery->last_error) title="{{ $delivery->last_error }}" @endif>
                            {{ $delivery->last_error ? Str::limit($delivery->last_error, 48) : '—' }}
                        </td>
                        <td class="py-2">
                            @if ($delivery->status->isReplayable())
                                <button
                                    type="button"
                                    wire:click="replayDelivery({{ $delivery->id }})"
                                    class="text-zinc-400 underline"
                                >
                                    Replay
                                </button>
                            @endif
                        </td>
                    </tr>
                    @if ($delivery->deliveryAttempts->isNotEmpty())
                        <tr wire:key="delivery-attempts-{{ $delivery->id }}">
                            <td colspan="7" class="py-2 pl-4">
                                <details>
                                    <summary class="cursor-pointer text-zinc-400">Attempt log</summary>
                                    <table class="mt-2 w-full border-collapse text-left text-zinc-400">
                                        <thead>
                                            <tr class="border-b border-zinc-800">
                                                <th class="py-1 pr-4 font-normal">#</th>
                                                <th class="py-1 pr-4 font-normal">Result</th>
                                                <th class="py-1 pr-4 font-normal">Response</th>
                                                <th class="py-1 pr-4 font-normal">Duration</th>
                                                <th class="py-1 pr-4 font-normal">Error</th>
                                                <th class="py-1 font-normal">At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($delivery->deliveryAttempts as $attempt)
                                                <tr wire:key="delivery-attempt-{{ $attempt->id }}" class="border-b border-zinc-800">
                                                    <td class="py-1 pr-4 text-zinc-50">{{ $attempt->attempt_number }}</td>
                                                    <td class="py-1 pr-4 text-zinc-50">{{ $attempt->result->value }}</td>
                                                    <td class="py-1 pr-4 text-zinc-50">{{ $attempt->response_status ?? '—' }}</td>
                                                    <td class="py-1 pr-4 text-zinc-50">{{ $attempt->duration_ms }} ms</td>
                                                    <td class="py-1 pr-4 text-zinc-50">{{ $attempt->error ?? '—' }}</td>
                                                    <td class="py-1 text-zinc-50">{{ $attempt->created_at->toDateTimeString() }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </details>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
</div>
