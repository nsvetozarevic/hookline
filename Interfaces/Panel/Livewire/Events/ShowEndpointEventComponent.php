<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire\Events;

use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.panel')]
class ShowEndpointEventComponent extends Component
{
    private const int DISPLAY_PAYLOAD_BYTES = 64 * 1024;

    public EndpointEvent $endpointEvent;

    public function mount(): void
    {
        $this->authorize('view', $this->endpointEvent->endpoint);
    }

    public function render(): View
    {
        $this->endpointEvent->loadMissing('endpoint');

        $formattedPayload = $this->formattedPayload();
        $payloadIsTruncated = strlen($formattedPayload) > self::DISPLAY_PAYLOAD_BYTES;

        return view('panel.events.show', [
            'payloadForDisplay' => $payloadIsTruncated
                ? substr($formattedPayload, 0, self::DISPLAY_PAYLOAD_BYTES)
                : $formattedPayload,
            'payloadIsTruncated' => $payloadIsTruncated,
        ]);
    }

    private function formattedPayload(): string
    {
        $payload = $this->endpointEvent->payload;
        $decoded = json_decode($payload);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $payload;
        }

        $prettyPrintedPayload = json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return is_string($prettyPrintedPayload) ? $prettyPrintedPayload : $payload;
    }
}
