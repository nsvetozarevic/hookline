<?php

namespace Domain\Endpoint\Data;

final readonly class CaptureWebhookResult
{
    private function __construct(
        public int $status,
        public string $deduplicationKey,
    ) {
    }

    public static function accepted(string $deduplicationKey): self
    {
        return new self(202, $deduplicationKey);
    }

    public static function duplicate(string $deduplicationKey): self
    {
        return new self(200, $deduplicationKey);
    }
}
