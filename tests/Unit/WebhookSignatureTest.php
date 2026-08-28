<?php

declare(strict_types=1);

namespace Tests\Unit;

use Domain\Webhook\Utility\WebhookSecret;
use Domain\Webhook\Utility\WebhookSignature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    #[Test]
    public function valid_payload_passes(): void
    {
        $this->assertTrue(WebhookSignature::verify(...$this->validPayload()));
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

        $this->assertTrue(WebhookSignature::verify(...$payload));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPayloads')]
    #[Test]
    public function invalid_payload_fails(array $overrides): void
    {
        $payload = [...$this->validPayload(), ...$overrides];

        $this->assertFalse(WebhookSignature::verify(...$payload));
    }

    #[Test]
    public function valid_signature_over_a_webhook_id_containing_a_dot_fails(): void
    {
        $payload = [...$this->validPayload(), 'webhookId' => 'msg.evil'];
        $payload['webhookSignatureHeader'] = $this->sign(
            webhookSecret: $payload['webhookSecret'],
            webhookId: $payload['webhookId'],
            webhookTimestamp: $payload['webhookTimestamp'],
            webhookRawRequestBody: $payload['webhookRawRequestBody'],
        );

        $this->assertFalse(WebhookSignature::verify(...$payload));
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'empty signing secret' => [['webhookSecret' => '']],
            'empty signature header' => [['webhookSignatureHeader' => '']],
            'empty webhook id' => [['webhookId' => '']],
            'webhook id containing a dot' => [['webhookId' => 'msg.evil']],
            'non-digit timestamp' => [['webhookTimestamp' => 'not-a-time']],
            'signing secret that is not whsec base64' => [['webhookSecret' => 'not-whsec']],
            'stale timestamp' => [['currentUnixTimestamp' => 1700000000 + 301]],
            'future timestamp' => [['currentUnixTimestamp' => 1700000000 - 301]],
            'id swap' => [['webhookId' => 'msg_other']],
            'all garbage signature list' => [['webhookSignatureHeader' => 'v1,not-a-real-signature v1,also-garbage']],
        ];
    }

    /**
     * @return array{
     *     webhookSecret: string,
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
            'webhookSecret' => 'whsec_YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=',
            'webhookId' => 'msg_test001',
            'webhookTimestamp' => '1700000000',
            'webhookRawRequestBody' => '{"ok":true}',
            'webhookSignatureHeader' => 'v1,6i3D5wZeiIzy3PoL6UZXllZiIQPOq+DBB87Zcj2wsk0=',
            'toleranceSeconds' => 300,
            'currentUnixTimestamp' => 1700000000,
        ];
    }

    private function sign(
        string $webhookSecret,
        string $webhookId,
        string $webhookTimestamp,
        string $webhookRawRequestBody,
    ): string {
        $secret = WebhookSecret::decode($webhookSecret);
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
