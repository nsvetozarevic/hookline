<?php

namespace Domain\Endpoint\Data;

use Domain\Endpoint\Models\Endpoint;

final readonly class CaptureWebhookData
{
    /**
     * @param array<string, string> $capturedHeaders
     */
    public function __construct(
        public Endpoint $endpoint,
        public string $rawRequestBody,
        public array $capturedHeaders,
        public ?string $hooklineEventId,
    ) {
    }
}
