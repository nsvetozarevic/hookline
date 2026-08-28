<?php

declare(strict_types=1);

namespace Domain\Delivery\Utility;

use DateTimeImmutable;
use DateTimeInterface;
use Domain\Delivery\Models\DestinationSigningSecret;
use Domain\Webhook\Utility\WebhookSecret;
use Domain\Webhook\Utility\WebhookSignature;

class OutboundSignature
{
    /**
     * @param  iterable<DestinationSigningSecret>  $signingSecrets
     * @param  array<string, string>  $capturedHeaders
     * @param  array<string, string>  $destinationHeaders
     * @return array<string, string>
     */
    public static function headers(
        int|string $deliveryId,
        int $sentAtUnix,
        string $body,
        iterable $signingSecrets,
        array $capturedHeaders = [],
        array $destinationHeaders = [],
        ?DateTimeInterface $now = null,
    ): array {
        $now ??= new DateTimeImmutable();
        $webhookId = (string) $deliveryId;
        $webhookTimestamp = (string) $sentAtUnix;

        $hooklineHeaders = [
            'content-type' => $capturedHeaders['content-type'] ?? 'application/json',
            'webhook-id' => $webhookId,
            'webhook-timestamp' => $webhookTimestamp,
            'webhook-signature' => implode(' ', self::signatures(
                $signingSecrets,
                $webhookId,
                $webhookTimestamp,
                $body,
                $now,
            )),
        ];

        return array_merge(
            $destinationHeaders,
            $hooklineHeaders,
        );
    }

    /**
     * @param  iterable<DestinationSigningSecret>  $signingSecrets
     * @return list<string>
     */
    private static function signatures(
        iterable $signingSecrets,
        string $webhookId,
        string $webhookTimestamp,
        string $body,
        DateTimeInterface $now,
    ): array {
        $signatureEntries = [];

        foreach ($signingSecrets as $signingSecret) {
            if ($signingSecret->expires_at !== null && $signingSecret->expires_at <= $now) {
                continue;
            }

            $decodedSecret = WebhookSecret::decode($signingSecret->secret);
            if ($decodedSecret === null) {
                continue;
            }

            $signatureEntries[] = WebhookSignature::signV1(
                $decodedSecret,
                $webhookId,
                $webhookTimestamp,
                $body,
            );
        }

        return $signatureEntries;
    }
}
