<?php

declare(strict_types=1);

namespace Tests\Unit;

use Domain\Webhook\Utility\WebhookSecret;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WebhookSecretTest extends TestCase
{
    #[Test]
    public function minted_secret_decodes_to_thirty_two_bytes(): void
    {
        $printable = WebhookSecret::mint();
        $secret = WebhookSecret::decode($printable);

        $this->assertNotNull($secret);
        $this->assertSame(32, strlen($secret));
    }

    #[Test]
    public function decode_rejects_a_string_that_is_not_whsec_base64(): void
    {
        $this->assertNull(WebhookSecret::decode('not-whsec'));
        $this->assertNull(WebhookSecret::decode('whsec_'));
        $this->assertNull(WebhookSecret::decode('whsec_!!!'));
    }
}
