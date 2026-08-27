<?php

declare(strict_types=1);

namespace Tests\Unit;

use Domain\Endpoint\Utility\SigningSecret;
use Domain\Endpoint\Utility\WebhookSignatureVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookSignatureVerifierTest extends TestCase
{
    #[Test]
    public function valid_payload_passes(): void
    {
        $this->assertTrue((new WebhookSignatureVerifier())->verify(...$this->validPayload()));
    }

    #[Test]
    public function signature_list_with_one_valid_entry_passes(): void
    {
        $payload = $this->validPayload();
        $payload['webhookSignatureHeader'] = sprintf(
            '%s %s',
            'v1,not-a-real-signature',
            $payload['webhookSignatureHeader'],
        );

        $this->assertTrue((new WebhookSignatureVerifier())->verify(...$payload));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPayloads')]
    #[Test]
    public function invalid_payload_fails(array $overrides): void
    {
        $payload = [...$this->validPayload(), ...$overrides];

        $this->assertFalse((new WebhookSignatureVerifier())->verify(...$payload));
    }

    #[Test]
    public function valid_signature_over_a_webhook_id_containing_a_dot_fails(): void
    {
        $payload = [...$this->validPayload(), 'webhookId' => 'msg.evil'];
        $payload['webhookSignatureHeader'] = $this->sign(
            signingSecret: $payload['signingSecret'],
            webhookId: $payload['webhookId'],
            webhookTimestamp: $payload['webhookTimestamp'],
            webhookRawRequestBody: $payload['webhookRawRequestBody'],
        );

        $this->assertFalse((new WebhookSignatureVerifier())->verify(...$payload));
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'empty signing secret' => [['signingSecret' => '']],
            'empty signature header' => [['webhookSignatureHeader' => '']],
            'empty webhook id' => [['webhookId' => '']],
            'webhook id containing a dot' => [['webhookId' => 'msg.evil']],
            'non-digit timestamp' => [['webhookTimestamp' => 'not-a-time']],
            'signing secret that is not whsec base64' => [['signingSecret' => 'not-whsec']],
            'stale timestamp' => [['currentUnixTimestamp' => 1700000000 + 301]],
            'future timestamp' => [['currentUnixTimestamp' => 1700000000 - 301]],
            'id swap' => [['webhookId' => 'msg_other']],
            'all garbage signature list' => [['webhookSignatureHeader' => 'v1,not-a-real-signature v1,also-garbage']],
        ];
    }

    /**
     * @return array{
     *     signingSecret: string,
     *     webhookId: string,
     *     webhookTimestamp: string,
     *     webhookRawRequestBody: string,
     *     webhookSignatureHeader: string,
     *     toleranceSeconds: int,
     *     currentUnixTimestamp: int,
     * }
     */
    private function validPayload(): array
    {
        return [
            'signingSecret' => 'whsec_YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=',
            'webhookId' => 'msg_test001',
            'webhookTimestamp' => '1700000000',
            'webhookRawRequestBody' => '{"ok":true}',
            'webhookSignatureHeader' => 'v1,6i3D5wZeiIzy3PoL6UZXllZiIQPOq+DBB87Zcj2wsk0=',
            'toleranceSeconds' => 300,
            'currentUnixTimestamp' => 1700000000,
        ];
    }

    private function sign(
        string $signingSecret,
        string $webhookId,
        string $webhookTimestamp,
        string $webhookRawRequestBody,
    ): string {
        $secret = SigningSecret::decode($signingSecret);
        $this->assertNotNull($secret);

        $hash = hash_hmac(
            'sha256',
            sprintf('%s.%s.%s', $webhookId, $webhookTimestamp, $webhookRawRequestBody),
            $secret,
            true,
        );

        return sprintf('v1,%s', base64_encode($hash));
    }
}
