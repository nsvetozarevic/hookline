@use(App\Routing\WebRoute)
@use(Illuminate\Support\Str)

<div>
    <nav class="hl-breadcrumb">
        <a href="{{ route(WebRoute::IndexEndpoints) }}">Endpoints</a>
        <span class="text-slate-300"> / </span>
        <a href="{{ route(WebRoute::ShowEndpoints, $endpointEvent->endpoint) }}">{{ $endpointEvent->endpoint->name }}</a>
        <span class="text-slate-300"> / </span>
        <span class="text-slate-700">#{{ $endpointEvent->id }}</span>
    </nav>

    <h1 class="hl-page-title mt-4">Event #{{ $endpointEvent->id }}</h1>
    <p class="hl-muted mt-1">
        <span class="font-mono" title="{{ $endpointEvent->deduplication_key }}">{{ $endpointEvent->deduplication_key }}</span>
        <span> · {{ $endpointEvent->created_at->toDateTimeString() }}</span>
    </p>

    <div class="hl-card mt-8">
        <h2 class="hl-section-title">Headers</h2>
        @if ($endpointEvent->headers === [])
            <p class="hl-muted mt-4">No headers.</p>
        @else
            <table class="hl-table mt-4">
                <tbody>
                    @foreach ($endpointEvent->headers as $name => $value)
                        <tr>
                            <th class="w-1/3 font-medium text-slate-500">{{ $name }}</th>
                            <td class="font-mono text-slate-800">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-8">
        <h2 class="hl-section-title">Payload</h2>
        <pre class="hl-code mt-3">{{ $payloadForDisplay }}</pre>
        @if ($payloadIsTruncated)
            <p class="hl-muted mt-3">Truncated, {{ (int) ceil(strlen($endpointEvent->payload) / 1024) }} KB total.</p>
        @endif
    </div>

    <div class="hl-card mt-8 overflow-x-auto">
        <h2 class="hl-section-title">Deliveries</h2>
        @if ($deliveries->isEmpty())
            <p class="hl-muted mt-4">No deliveries.</p>
        @else
            <table class="hl-table mt-4">
                <thead>
                    <tr>
                        <th>Destination</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Last response</th>
                        <th>Last result</th>
                        <th>Last error</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deliveries as $delivery)
                        <tr wire:key="delivery-{{ $delivery->id }}">
                            <td class="max-w-xs font-mono text-xs break-all">{{ $delivery->destination->url }}</td>
                            <td>
                                <span @class([
                                    'hl-badge',
                                    'hl-badge-succeeded' => $delivery->status->value === 'succeeded',
                                    'hl-badge-pending' => $delivery->status->value === 'pending',
                                    'hl-badge-in_flight' => $delivery->status->value === 'in_flight',
                                    'hl-badge-dead' => $delivery->status->value === 'dead',
                                ])>{{ $delivery->status->value }}</span>
                            </td>
                            <td>{{ $delivery->attempts }}</td>
                            <td class="font-mono">{{ $delivery->last_status_code ?? '-' }}</td>
                            <td class="font-mono">{{ $delivery->latestDeliveryAttempt?->result->value ?? '-' }}</td>
                            <td @if ($delivery->last_error) title="{{ $delivery->last_error }}" @endif>
                                {{ $delivery->last_error ? Str::limit($delivery->last_error, 48) : '-' }}
                            </td>
                            <td>
                                @if ($delivery->status->isReplayable())
                                    <button
                                        type="button"
                                        wire:click="replayDelivery({{ $delivery->id }})"
                                        class="hl-btn-link"
                                    >
                                        Replay
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @if ($delivery->deliveryAttempts->isNotEmpty())
                            <tr wire:key="delivery-attempts-{{ $delivery->id }}">
                                <td colspan="7" class="py-3 pl-2">
                                    <details>
                                        <summary class="hl-btn-link cursor-pointer list-none">Attempt log</summary>
                                        <table class="hl-table mt-3">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Result</th>
                                                    <th>Response</th>
                                                    <th>Duration</th>
                                                    <th>Error</th>
                                                    <th>At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($delivery->deliveryAttempts as $attempt)
                                                    <tr wire:key="delivery-attempt-{{ $attempt->id }}">
                                                        <td>{{ $attempt->attempt_number }}</td>
                                                        <td class="font-mono">{{ $attempt->result->value }}</td>
                                                        <td class="font-mono">{{ $attempt->response_status ?? '-' }}</td>
                                                        <td>{{ $attempt->duration_ms }} ms</td>
                                                        <td>{{ $attempt->error ?? '-' }}</td>
                                                        <td class="whitespace-nowrap">{{ $attempt->created_at->toDateTimeString() }}</td>
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
</div>
