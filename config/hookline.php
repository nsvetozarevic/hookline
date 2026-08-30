<?php

declare(strict_types=1);

return [
    'capture' => [
        'max_body_kilobytes' => 1024,
        // Matches `endpoint_events.deduplication_key` (`string` = VARCHAR(255)).
        'max_deduplication_key_length' => 255,
        'timestamp_tolerance_seconds' => 300,
        'rate_limit_per_minute' => 120,
        // Lowercase names. Do not persist the signature — it is verified, not stored.
        'captured_header_names' => [
            'content-type',
            'user-agent',
            'webhook-id',
            'webhook-timestamp',
        ],
    ],
    'webhooks' => [
        'secret_rotation_grace_hours' => 48,
    ],
    'delivery' => [
        'default_timeout_seconds' => 5,
        'default_max_attempts' => 8,
        'backoff_base_seconds' => 10,
        'backoff_cap_seconds' => 3600,
        'response_snippet_bytes' => 1024,
        'in_flight_timeout_seconds' => 300,
    ],
];
