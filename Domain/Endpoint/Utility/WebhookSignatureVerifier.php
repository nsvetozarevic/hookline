<?php

declare(strict_types=1);

namespace Domain\Endpoint\Utility;

use Illuminate\Support\Facades\Log;

class WebhookSignatureVerifier
{
    private const string SIGNATURE_VERSION_PREFIX = 'v1,';

    public function verify(
        string $signingSecret,
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

        Log::info('Webhook signature verification started.', $context);

        if ($signingSecret === '') {
            return $this->reject('empty_signing_secret', $context);
        }

        if ($webhookSignatureHeader === '') {
            return $this->reject('empty_signature_header', $context);
        }

        if ($webhookId === '') {
            return $this->reject('empty_webhook_id', $context);
        }

        if (str_contains($webhookId, '.')) {
            return $this->reject('webhook_id_contains_dot', $context);
        }

        if (! ctype_digit($webhookTimestamp)) {
            return $this->reject('timestamp_not_unix_seconds', $context);
        }

        $secret = SigningSecret::decode($signingSecret);
        if ($secret === null) {
            return $this->reject('signing_secret_not_whsec_base64', $context);
        }

        $currentUnixTimestamp ??= time();
        $timestampAgeSeconds = abs($currentUnixTimestamp - (int) $webhookTimestamp);

        if ($timestampAgeSeconds > $toleranceSeconds) {
            return $this->reject('timestamp_outside_tolerance', [
                ...$context,
                'age_seconds' => $timestampAgeSeconds,
                'tolerance_seconds' => $toleranceSeconds,
            ]);
        }

        $expectedSignature = $this->expectedSignature(
            $secret,
            $webhookId,
            $webhookTimestamp,
            $webhookRawRequestBody,
        );

        $signatureEntries = $this->signatureEntries($webhookSignatureHeader);

        foreach ($signatureEntries as $signatureEntry) {
            if (hash_equals($expectedSignature, $signatureEntry)) {
                return true;
            }
        }

        return $this->reject('signature_mismatch', [
            ...$context,
            'signature_entry_count' => count($signatureEntries),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function reject(string $reason, array $context): false
    {
        Log::info('Webhook signature verification failed.', [
            'reason' => $reason,
            ...$context,
        ]);

        return false;
    }

    private function expectedSignature(
        string $secret,
        string $webhookId,
        string $webhookTimestamp,
        string $webhookRawRequestBody,
    ): string {
        $hash = hash_hmac(
            'sha256',
            sprintf('%s.%s.%s', $webhookId, $webhookTimestamp, $webhookRawRequestBody),
            $secret,
            true,
        );

        return sprintf('%s%s', self::SIGNATURE_VERSION_PREFIX, base64_encode($hash));
    }

    /**
     * @return list<string>
     */
    private function signatureEntries(string $webhookSignatureHeader): array
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
