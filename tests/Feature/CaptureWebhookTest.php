<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Routing\WebRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaptureWebhookTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function unknown_endpoint_token_returns_not_found(): void
    {
        $response = $this->postCapture('missing-token', '{}', $this->signedHeaders('secret', '{}'));

        $response->assertNotFound();
    }

    #[Test]
    public function capture_is_rate_limited(): void
    {
        config(['hookline.capture.rate_limit_per_minute' => 1]);

        $captureToken = 'missing-token';
        RateLimiter::hit(md5('capture'.$captureToken));

        $this->postCapture($captureToken, '{}', $this->signedHeaders('secret', '{}'))
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
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
    public function invalid_signature_is_rejected(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $headers = $this->signedHeaders($endpoint->signing_secret, $body);
        $headers['X-Hookline-Signature'] = '00';

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
    public function payload_over_size_cap_is_rejected(): void
    {
        config(['hookline.capture.max_body_kilobytes' => 1]);

        $endpoint = Endpoint::factory()->create();
        $body = str_repeat('a', 1025);

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders($endpoint->signing_secret, $body),
        );

        $response->assertStatus(413);
        $this->assertDatabaseCount('endpoint_events', 0);
    }

    #[Test]
    public function overlong_hookline_event_id_is_rejected(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $hooklineEventId = str_repeat('a', 256);

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders(
                signingSecret: $endpoint->signing_secret,
                body: $body,
                hooklineEventId: $hooklineEventId,
            ),
        );

        $response->assertBadRequest()
            ->assertJson(['message' => 'Event id too long.']);
        $this->assertDatabaseCount('endpoint_events', 0);
    }

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
                hooklineEventId: 'evt_1',
            ),
        );

        $response->assertAccepted()
            ->assertJsonPath('deduplication_key', 'evt_1');

        $this->assertDatabaseHas('endpoint_events', [
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => 'evt_1',
            'payload' => $body,
        ]);
    }

    #[Test]
    public function captured_headers_follow_config_and_omit_the_signature(): void
    {
        config(['hookline.capture.captured_header_names' => ['content-type', 'x-hookline-event-id']]);

        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $headers = $this->signedHeaders(
            signingSecret: $endpoint->signing_secret,
            body: $body,
            hooklineEventId: 'evt_headers',
        );
        $headers['User-Agent'] = 'hookline-test';

        $this->postCapture($endpoint->capture_token, $body, $headers)->assertAccepted();

        $endpointEvent = $endpoint->endpointEvents()
            ->where('deduplication_key', 'evt_headers')
            ->firstOrFail();

        $this->assertSame(
            [
                'content-type' => 'application/json',
                'x-hookline-event-id' => 'evt_headers',
            ],
            $endpointEvent->headers,
        );
    }

    #[Test]
    public function duplicate_capture_returns_the_same_event(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        EndpointEvent::factory()->create([
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => 'evt_dup',
            'payload' => $body,
        ]);

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders(
                signingSecret: $endpoint->signing_secret,
                body: $body,
                hooklineEventId: 'evt_dup',
            ),
        );

        $response->assertOk()
            ->assertJsonPath('deduplication_key', 'evt_dup');

        $this->assertDatabaseCount('endpoint_events', 1);
    }

    #[Test]
    public function duplicate_event_id_with_a_different_body_keeps_the_original_payload(): void
    {
        $endpoint = Endpoint::factory()->create();
        $originalPayload = '{"ok":true}';
        EndpointEvent::factory()->create([
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => 'evt_dup',
            'payload' => $originalPayload,
        ]);

        $newBody = '{"ok":false}';

        $response = $this->postCapture(
            $endpoint->capture_token,
            $newBody,
            $this->signedHeaders(
                signingSecret: $endpoint->signing_secret,
                body: $newBody,
                hooklineEventId: 'evt_dup',
            ),
        );

        $response->assertOk()
            ->assertJsonPath('deduplication_key', 'evt_dup');

        $this->assertDatabaseCount('endpoint_events', 1);
        $this->assertDatabaseHas('endpoint_events', [
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => 'evt_dup',
            'payload' => $originalPayload,
        ]);
    }

    #[Test]
    public function missing_hookline_event_id_uses_body_hash_as_deduplication_key(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';
        $deduplicationKey = hash('sha256', $body);
        EndpointEvent::factory()->create([
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => $deduplicationKey,
            'payload' => $body,
        ]);

        $response = $this->postCapture(
            $endpoint->capture_token,
            $body,
            $this->signedHeaders($endpoint->signing_secret, $body),
        );

        $response->assertOk()
            ->assertJsonPath('deduplication_key', $deduplicationKey);

        $this->assertDatabaseCount('endpoint_events', 1);
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
    private function signedHeaders(string $signingSecret, string $body, ?int $timestamp = null, ?string $hooklineEventId = null): array
    {
        $timestamp ??= time();
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'X-Hookline-Timestamp' => (string) $timestamp,
            'X-Hookline-Signature' => hash_hmac('sha256', $timestamp.'.'.$body, $signingSecret),
        ];

        if ($hooklineEventId !== null) {
            $headers['X-Hookline-Event-Id'] = $hooklineEventId;
        }

        return $headers;
    }
}
