<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Routing\WebRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\Endpoint\Utility\SigningSecret;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaptureWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const string DUMMY_SIGNING_SECRET = 'whsec_YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=';

    #[Test]
    public function valid_capture_persists_event_and_returns_accepted(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders(
                signingSecret: $endpoint->signing_secret,
                body: $body,
                webhookId: 'msg_1',
            ),
        );

        $response->assertAccepted()
            ->assertJsonPath('deduplication_key', 'msg_1');

        $this->assertDatabaseHas('endpoint_events', [
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => 'msg_1',
            'payload' => $body,
        ]);
    }

    #[Test]
    public function signature_list_with_one_valid_entry_is_accepted(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $headers = $this->signedHeaders(
            signingSecret: $endpoint->signing_secret,
            body: $body,
            webhookId: 'msg_list',
        );
        $headers['webhook-signature'] = sprintf(
            '%s %s',
            'v1,not-a-real-signature',
            $headers['webhook-signature'],
        );

        $response = $this->postCapture($endpoint->capture_token, $body, $headers);

        $response->assertAccepted()
            ->assertJsonPath('deduplication_key', 'msg_list');

        $this->assertDatabaseHas('endpoint_events', [
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => 'msg_list',
            'payload' => $body,
        ]);
    }

    #[Test]
    public function captured_headers_follow_config_and_omit_the_signature(): void
    {
        config(['hookline.capture.captured_header_names' => ['content-type', 'webhook-id']]);

        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $headers = $this->signedHeaders(
            signingSecret: $endpoint->signing_secret,
            body: $body,
            webhookId: 'msg_headers',
        );
        $headers['User-Agent'] = 'hookline-test';

        $this->postCapture($endpoint->capture_token, $body, $headers)->assertAccepted();

        $endpointEvent = $endpoint->endpointEvents()
            ->where('deduplication_key', 'msg_headers')
            ->firstOrFail();

        $this->assertSame('application/json', $endpointEvent->headers['content-type']);
        $this->assertSame('msg_headers', $endpointEvent->headers['webhook-id']);
        $this->assertArrayNotHasKey('webhook-signature', $endpointEvent->headers);
        $this->assertArrayNotHasKey('user-agent', $endpointEvent->headers);
        $this->assertCount(2, $endpointEvent->headers);
    }

    #[Test]
    public function duplicate_capture_does_not_create_a_new_event(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        EndpointEvent::factory()->create([
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => 'msg_dup',
            'payload' => $body,
        ]);

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders(
                signingSecret: $endpoint->signing_secret,
                body: $body,
                webhookId: 'msg_dup',
            ),
        );

        $response->assertOk()
            ->assertJsonPath('deduplication_key', 'msg_dup');

        $this->assertDatabaseCount('endpoint_events', 1);
    }

    #[Test]
    public function duplicate_webhook_id_with_a_different_body_keeps_the_original_payload(): void
    {
        $endpoint = Endpoint::factory()->create();
        $originalPayload = '{"ok":true}';
        EndpointEvent::factory()->create([
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => 'msg_dup',
            'payload' => $originalPayload,
        ]);

        $newBody = '{"ok":false}';

        $response = $this->postCapture(
            $endpoint->capture_token,
            $newBody,
            $this->signedHeaders(
                signingSecret: $endpoint->signing_secret,
                body: $newBody,
                webhookId: 'msg_dup',
            ),
        );

        $response->assertOk()
            ->assertJsonPath('deduplication_key', 'msg_dup');

        $this->assertDatabaseCount('endpoint_events', 1);
        $this->assertDatabaseHas('endpoint_events', [
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => 'msg_dup',
            'payload' => $originalPayload,
        ]);
    }

    #[Test]
    public function unknown_endpoint_token_returns_not_found(): void
    {
        $response = $this->postCapture('missing-token', '{}', $this->signedHeaders(self::DUMMY_SIGNING_SECRET, '{}'));

        $response->assertNotFound();
    }

    #[Test]
    public function inactive_endpoint_returns_not_found(): void
    {
        $endpoint = Endpoint::factory()->inactive()->create();
        $body = '{"ok":true}';

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders($endpoint->signing_secret, $body),
        );

        $response->assertNotFound();

        $this->assertDatabaseCount('endpoint_events', 0);
    }

    #[Test]
    public function capture_is_rate_limited(): void
    {
        config(['hookline.capture.rate_limit_per_minute' => 1]);

        $captureToken = 'missing-token';
        RateLimiter::hit(md5(sprintf('%s%s', 'capture', $captureToken)));

        $this->postCapture($captureToken, '{}', $this->signedHeaders(self::DUMMY_SIGNING_SECRET, '{}'))
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
    }

    #[Test]
    public function payload_over_size_cap_is_rejected_before_signature(): void
    {
        config(['hookline.capture.max_body_kilobytes' => 1]);

        $endpoint = Endpoint::factory()->create();
        $body = str_repeat('a', 1025);
        $headers = $this->signedHeaders($endpoint->signing_secret, $body);
        $headers['webhook-signature'] = 'v1,00';

        $response = $this->postCapture($endpoint->capture_token, $body, $headers);

        $response->assertStatus(413);
        $this->assertDatabaseCount('endpoint_events', 0);
    }

    #[Test]
    public function invalid_signature_is_rejected(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $headers = $this->signedHeaders($endpoint->signing_secret, $body);
        $headers['webhook-signature'] = 'v1,00';

        $response = $this->postCapture($endpoint->capture_token, $body, $headers);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid webhook signature.']);

        $this->assertDatabaseCount('endpoint_events', 0);
    }

    #[Test]
    public function missing_webhook_id_is_rejected(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $headers = $this->signedHeaders($endpoint->signing_secret, $body);
        unset($headers['webhook-id']);

        $response = $this->postCapture($endpoint->capture_token, $body, $headers);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid webhook signature.']);

        $this->assertDatabaseCount('endpoint_events', 0);
    }

    #[Test]
    public function id_swap_is_rejected(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $headers = $this->signedHeaders(
            signingSecret: $endpoint->signing_secret,
            body: $body,
            webhookId: 'msg_a',
        );
        $headers['webhook-id'] = 'msg_b';

        $response = $this->postCapture($endpoint->capture_token, $body, $headers);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid webhook signature.']);

        $this->assertDatabaseCount('endpoint_events', 0);
    }

    #[Test]
    public function stale_timestamp_is_rejected(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $stale = time() - 301;

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders($endpoint->signing_secret, $body, $stale),
        );

        $response->assertUnauthorized();
        $this->assertDatabaseCount('endpoint_events', 0);
    }

    #[Test]
    public function future_timestamp_is_rejected(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $future = time() + 301;

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders($endpoint->signing_secret, $body, $future),
        );

        $response->assertUnauthorized();
        $this->assertDatabaseCount('endpoint_events', 0);
    }

    #[Test]
    public function overlong_webhook_id_with_an_invalid_signature_is_unauthorized(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $headers = $this->signedHeaders(
            signingSecret: $endpoint->signing_secret,
            body: $body,
            webhookId: str_repeat('a', 256),
        );
        $headers['webhook-signature'] = 'v1,00';

        $response = $this->postCapture($endpoint->capture_token, $body, $headers);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid webhook signature.']);
        $this->assertDatabaseCount('endpoint_events', 0);
    }

    #[Test]
    public function overlong_webhook_id_is_rejected(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $webhookId = str_repeat('a', 256);

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders(
                signingSecret: $endpoint->signing_secret,
                body: $body,
                webhookId: $webhookId,
            ),
        );

        $response->assertBadRequest()
            ->assertJson(['message' => 'Event id too long.']);
        $this->assertDatabaseCount('endpoint_events', 0);
    }

    /**
     * HMAC is over the raw body. `post()` / `postJson()` send arrays or re-encode JSON.
     *
     * @param  array<string, string>  $headers
     */
    private function postCapture(string $captureToken, string $body, array $headers): \Illuminate\Testing\TestResponse
    {
        return $this->call(
            method: 'POST',
            uri: route(WebRoute::Capture, ['captureToken' => $captureToken], absolute: false),
            server: $this->transformHeadersToServerVars($headers),
            content: $body,
        );
    }

    /**
     * @return array<string, string>
     */
    private function signedHeaders(
        string $signingSecret,
        string $body,
        ?int $timestamp = null,
        string $webhookId = 'msg_test001',
    ): array {
        $timestamp ??= time();
        $secret = SigningSecret::decode($signingSecret);
        $this->assertNotNull($secret);

        $digest = hash_hmac(
            'sha256',
            sprintf('%s.%s.%s', $webhookId, (string) $timestamp, $body),
            $secret,
            true,
        );

        return [
            'CONTENT_TYPE' => 'application/json',
            'webhook-id' => $webhookId,
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => sprintf('v1,%s', base64_encode($digest)),
        ];
    }
}
