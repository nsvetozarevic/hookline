<?php

declare(strict_types=1);

namespace Tests\Unit;

use Domain\Delivery\Models\DestinationSigningSecret;
use Domain\Delivery\Utility\OutboundSignature;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OutboundSignatureTest extends TestCase
{
    private const string SECRET_A = 'whsec_YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=';

    private const string SECRET_B = 'whsec_YmJiYmJiYmJiYmJiYmJiYmJiYmJiYmJiYmJiYmJiYmI=';

    private const string SIGNATURE_A = 'v1,6i3D5wZeiIzy3PoL6UZXllZiIQPOq+DBB87Zcj2wsk0=';

    private const string SIGNATURE_B = 'v1,Kc/mKwl2Znyc8T3vM52vkNngo4X9xU0e2qkh7rVqzHs=';

    #[Test]
    public function known_secret_produces_the_standard_webhooks_signature(): void
    {
        $headers = $this->headers(signingSecrets: [$this->secret(self::SECRET_A)]);

        $this->assertSame(self::SIGNATURE_A, $headers['webhook-signature']);
        $this->assertSame('msg_test001', $headers['webhook-id']);
        $this->assertSame('1700000000', $headers['webhook-timestamp']);
        $this->assertSame('application/json', $headers['content-type']);

        $headers = $this->headers(deliveryId: 42);
        $this->assertSame('42', $headers['webhook-id']);
    }

    #[Test]
    public function mid_rotation_emits_a_space_delimited_signature_list(): void
    {
        $headers = $this->headers(signingSecrets: [
            $this->secret(self::SECRET_A),
            $this->secret(self::SECRET_B, expiresAt: new Carbon('2024-01-01 01:00:00')),
        ]);

        $this->assertSame(
            sprintf('%s %s', self::SIGNATURE_A, self::SIGNATURE_B),
            $headers['webhook-signature'],
        );
    }

    #[Test]
    public function expired_secrets_are_omitted(): void
    {
        $headers = $this->headers(signingSecrets: [
            $this->secret(self::SECRET_A),
            $this->secret(self::SECRET_B, expiresAt: new Carbon('2023-12-31 23:59:59')),
        ]);

        $this->assertSame(self::SIGNATURE_A, $headers['webhook-signature']);
    }

    #[Test]
    public function destination_headers_are_merged_without_clobbering_webhook_signature(): void
    {
        $headers = $this->headers(
            capturedHeaders: ['content-type' => 'application/json'],
            destinationHeaders: [
                'Content-Type' => 'text/plain',
                'webhook-signature' => 'v1,forged',
                'X-Extra' => 'yes',
            ],
        );

        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame('text/plain', $headers['Content-Type']);
        $this->assertSame(self::SIGNATURE_A, $headers['webhook-signature']);
        $this->assertSame('yes', $headers['X-Extra']);
    }

    /**
     * @param  list<DestinationSigningSecret>  $signingSecrets
     * @param  array<string, string>  $capturedHeaders
     * @param  array<string, string>  $destinationHeaders
     * @return array<string, string>
     */
    private function headers(
        int|string $deliveryId = 'msg_test001',
        array $signingSecrets = [],
        array $capturedHeaders = [],
        array $destinationHeaders = [],
    ): array {
        if ($signingSecrets === []) {
            $signingSecrets = [$this->secret(self::SECRET_A)];
        }

        return OutboundSignature::headers(
            deliveryId: $deliveryId,
            sentAtUnix: 1700000000,
            body: '{"ok":true}',
            signingSecrets: $signingSecrets,
            capturedHeaders: $capturedHeaders,
            destinationHeaders: $destinationHeaders,
            now: new Carbon('2024-01-01 00:00:00'),
        );
    }

    private function secret(string $secret, ?Carbon $expiresAt = null): DestinationSigningSecret
    {
        $destinationSigningSecret = new DestinationSigningSecret();
        $destinationSigningSecret->secret = $secret;
        $destinationSigningSecret->expires_at = $expiresAt;

        return $destinationSigningSecret;
    }
}
