<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Routing\WebRoute;
use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\Webhook\Utility\WebhookSecret;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FanOutAtCaptureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function capture_creates_a_pending_delivery_for_each_active_destination(): void
    {
        $this->travelTo($now = now()->startOfSecond());

        Queue::fake();

        $endpoint = Endpoint::factory()->create();
        Destination::factory()->for($endpoint)->count(2)->create();
        Destination::factory()->for($endpoint)->inactive()->create();

        $body = '{"ok":true}';
        $webhookId = 'msg_fanout';

        $this->postCapture($endpoint, $body, $webhookId)->assertAccepted();

        $endpointEvent = EndpointEvent::query()
            ->where('endpoint_id', $endpoint->id)
            ->where('deduplication_key', $webhookId)
            ->firstOrFail();

        $this->assertDatabaseCount('deliveries', 2);
        $this->assertSame(2, Delivery::query()->where('endpoint_event_id', $endpointEvent->id)->count());

        $deliveries = Delivery::query()->where('endpoint_event_id', $endpointEvent->id)->get();
        foreach ($deliveries as $delivery) {
            $this->assertSame(DeliveryStatus::Pending, $delivery->status);
            $this->assertSame(0, $delivery->attempts);
            $this->assertEquals($now, $delivery->next_attempt_at);
        }
    }

    #[Test]
    public function capture_with_no_destinations_creates_no_deliveries(): void
    {
        $endpoint = Endpoint::factory()->create();
        $body = '{"ok":true}';

        $this->postCapture($endpoint, $body, 'msg_no_destinations')->assertAccepted();

        $this->assertDatabaseCount('deliveries', 0);
    }

    #[Test]
    public function duplicate_capture_does_not_fan_out_again(): void
    {
        $endpoint = Endpoint::factory()->create();
        Destination::factory()->for($endpoint)->count(2)->create();

        $body = '{"ok":true}';
        $webhookId = 'msg_dup_fanout';

        $endpointEvent = EndpointEvent::factory()->create([
            'endpoint_id' => $endpoint->id,
            'deduplication_key' => $webhookId,
            'payload' => $body,
        ]);

        $destinations = Destination::factory()->for($endpoint)->count(2)->create();
        foreach ($destinations as $destination) {
            Delivery::factory()->create([
                'endpoint_event_id' => $endpointEvent->id,
                'destination_id' => $destination->id,
            ]);
        }

        $this->postCapture($endpoint, $body, $webhookId)->assertOk();

        $this->assertDatabaseCount('deliveries', 2);
    }

    private function postCapture(Endpoint $endpoint, string $body, string $webhookId): \Illuminate\Testing\TestResponse
    {
        return $this->call(
            method: 'POST',
            uri: route(WebRoute::Capture, ['captureToken' => $endpoint->capture_token], absolute: false),
            server: $this->transformHeadersToServerVars($this->signedHeaders(
                signingSecret: $endpoint->currentSigningSecret->secret,
                body: $body,
                webhookId: $webhookId,
            )),
            content: $body,
        );
    }

    /**
     * @return array<string, string>
     */
    private function signedHeaders(string $signingSecret, string $body, string $webhookId): array
    {
        $timestamp = now()->timestamp;
        $secret = WebhookSecret::decode($signingSecret);
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
