<?php

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
            'x-hookline-timestamp',
            'x-hookline-event-id',
        ],
    ],
];
