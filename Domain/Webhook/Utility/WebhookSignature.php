<?php

declare(strict_types=1);

namespace Domain\Webhook\Utility;

use Illuminate\Support\Facades\Log;

class WebhookSignature
{
    public static function signV1(
        string $decodedSecret,
        string $webhookId,
        string $webhookTimestamp,
        string $rawRequestBody,
    ): string {
        $hash = hash_hmac(
            'sha256',
            sprintf('%s.%s.%s', $webhookId, $webhookTimestamp, $rawRequestBody),
            $decodedSecret,
            true,
        );

        return sprintf('v1,%s', base64_encode($hash));
    }

    public static function verify(
        string $webhookSecret,
        string $webhookId,
        string $webhookTimestamp,
        string $webhookRawRequestBody,
        string $webhookSignatureHeader,
        int $toleranceSeconds,
        ?int $currentUnixTimestamp = null,
    ): bool {
        $context = [
            'webhook_id' => $webhookId,
            'timestamp' => $webhookTimestamp,
        ];

        Log::channel('hookline')->info('Webhook signature verification started.', $context);

        if ($webhookSecret === '') {
            return self::reject('empty_signing_secret', $context);
        }

        if ($webhookSignatureHeader === '') {
            return self::reject('empty_signature_header', $context);
        }

        if ($webhookId === '') {
            return self::reject('empty_webhook_id', $context);
        }

        if (str_contains($webhookId, '.')) {
            return self::reject('webhook_id_contains_dot', $context);
        }

        if (! ctype_digit($webhookTimestamp)) {
            return self::reject('timestamp_not_unix_seconds', $context);
        }

        $secret = WebhookSecret::decode($webhookSecret);
        if ($secret === null) {
            return self::reject('signing_secret_not_whsec_base64', $context);
        }

        $currentUnixTimestamp ??= time();
        $timestampAgeSeconds = abs($currentUnixTimestamp - (int) $webhookTimestamp);

        if ($timestampAgeSeconds > $toleranceSeconds) {
            return self::reject('timestamp_outside_tolerance', [
                ...$context,
                'age_seconds' => $timestampAgeSeconds,
                'tolerance_seconds' => $toleranceSeconds,
            ]);
        }

        $expectedSignature = self::signV1(
            $secret,
            $webhookId,
            $webhookTimestamp,
            $webhookRawRequestBody,
        );

        $signatureEntries = self::signatureEntries($webhookSignatureHeader);

        foreach ($signatureEntries as $signatureEntry) {
            if (hash_equals($expectedSignature, $signatureEntry)) {
                return true;
            }
        }

        return self::reject('signature_mismatch', [
            ...$context,
            'signature_entry_count' => count($signatureEntries),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function reject(string $reason, array $context): false
    {
        Log::channel('hookline')->info('Webhook signature verification failed.', [
            'reason' => $reason,
            ...$context,
        ]);

        return false;
    }

    /**
     * @return list<string>
     */
    private static function signatureEntries(string $webhookSignatureHeader): array
    {
        $trimmedHeader = trim($webhookSignatureHeader);
        $splitEntries = preg_split('/\s+/', $trimmedHeader);
        if ($splitEntries === false) {
            return [];
        }

        $nonEmptyEntries = array_filter(
            $splitEntries,
            fn (string $signatureEntry): bool => $signatureEntry !== '',
        );

        return array_values($nonEmptyEntries);
    }
}
