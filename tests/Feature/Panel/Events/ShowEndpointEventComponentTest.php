<?php

declare(strict_types=1);

namespace Tests\Feature\Panel\Events;

use App\Routing\WebRoute;
use Domain\Delivery\Enums\DeliveryAttemptResult;
use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Jobs\DeliverDelivery;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\DeliveryAttempt;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Interfaces\Panel\Livewire\Events\ShowEndpointEventComponent;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowEndpointEventComponentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guests_to_login(): void
    {
        $endpointEvent = EndpointEvent::factory()->create();

        $this->get(route(WebRoute::ShowEvents, $endpointEvent))
            ->assertRedirect(route(WebRoute::ShowLogin));
    }

    #[Test]
    public function it_is_rendered_correctly(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create(['name' => 'Stripe']);
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'deduplication_key' => 'evt_visible',
        ]);

        $this->actingAs($user)
            ->get(route(WebRoute::ShowEvents, $endpointEvent))
            ->assertOk()
            ->assertSee('evt_visible', false)
            ->assertSee('Stripe', false);
    }

    #[Test]
    public function it_pretty_prints_json_payload_and_shows_headers(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'headers' => [
                'content-type' => 'application/json',
                'user-agent' => 'stripe-webhooks/1.0',
            ],
            'payload' => '{"ok":true}',
        ]);

        $this->actingAs($user);

        $prettyPrintedPayload = json_encode(
            json_decode('{"ok":true}'),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->assertOk()
            ->assertSee('content-type')
            ->assertSee('application/json')
            ->assertSee('user-agent')
            ->assertSee('stripe-webhooks/1.0')
            ->assertSee($prettyPrintedPayload)
            ->assertDontSee('Truncated,');
    }

    #[Test]
    public function it_shows_a_non_json_payload_raw(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'payload' => 'not-json <raw>',
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->assertOk()
            ->assertSee('not-json <raw>');
    }

    #[Test]
    public function it_caps_the_displayed_payload(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'payload' => str_repeat('a', 64 * 1024).'TAIL',
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->assertOk()
            ->assertDontSee('TAIL')
            ->assertSee('Truncated, 65 KB total.');
    }

    #[Test]
    public function it_shows_delivery_attempt_details(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create();
        $destination = Destination::factory()->for($endpoint)->create([
            'url' => 'https://example.com/webhook',
        ]);
        $delivery = Delivery::factory()->create([
            'endpoint_event_id' => $endpointEvent->id,
            'destination_id' => $destination->id,
            'status' => DeliveryStatus::Dead,
            'attempts' => 3,
            'last_status_code' => 502,
            'last_error' => 'HTTP 502',
        ]);
        DeliveryAttempt::factory()->create([
            'delivery_id' => $delivery->id,
            'attempt_number' => 3,
            'result' => DeliveryAttemptResult::Failed,
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->assertOk()
            ->assertSee('https://example.com/webhook')
            ->assertSee('dead')
            ->assertSee('3')
            ->assertSee('502')
            ->assertSee('failed')
            ->assertSee('HTTP 502')
            ->assertSee('Replay');
    }

    #[Test]
    public function it_shows_delivery_attempt_history(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create();
        $destination = Destination::factory()->for($endpoint)->create([
            'url' => 'https://example.com/webhook',
        ]);
        $delivery = Delivery::factory()->create([
            'endpoint_event_id' => $endpointEvent->id,
            'destination_id' => $destination->id,
            'status' => DeliveryStatus::Dead,
            'attempts' => 2,
        ]);
        DeliveryAttempt::factory()->create([
            'delivery_id' => $delivery->id,
            'attempt_number' => 1,
            'result' => DeliveryAttemptResult::Retryable,
            'response_status' => 503,
            'duration_ms' => 120,
            'error' => 'HTTP 503',
        ]);
        DeliveryAttempt::factory()->create([
            'delivery_id' => $delivery->id,
            'attempt_number' => 2,
            'result' => DeliveryAttemptResult::Failed,
            'response_status' => 500,
            'duration_ms' => 95,
            'error' => 'HTTP 500',
        ]);

        $this->actingAs($user);

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->assertOk()
            ->assertSee('Attempt log')
            ->assertSee('retryable')
            ->assertSee('failed')
            ->assertSee('503')
            ->assertSee('500')
            ->assertSee('120 ms')
            ->assertSee('95 ms');
    }

    #[Test]
    public function replay_delivery_resets_a_dead_delivery_and_dispatches_the_job(): void
    {
        $this->travelTo(now()->startOfSecond());

        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create();
        $destination = Destination::factory()->for($endpoint)->create([
            'url' => 'https://example.com/webhook',
        ]);
        $delivery = Delivery::factory()->create([
            'endpoint_event_id' => $endpointEvent->id,
            'destination_id' => $destination->id,
            'status' => DeliveryStatus::Dead,
            'attempts' => 2,
        ]);
        Queue::fake();

        $this->actingAs($user);

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->call('replayDelivery', $delivery->id);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        $this->assertSame(0, $delivery->attempts);

        Queue::assertPushed(DeliverDelivery::class, fn (DeliverDelivery $job): bool => $job->deliveryId === $delivery->id);
    }

    #[Test]
    public function replay_delivery_rejects_a_pending_delivery(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create();
        $destination = Destination::factory()->for($endpoint)->create();
        $delivery = Delivery::factory()->create([
            'endpoint_event_id' => $endpointEvent->id,
            'destination_id' => $destination->id,
            'status' => DeliveryStatus::Pending,
        ]);
        Queue::fake();

        $this->actingAs($user);
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(ShowEndpointEventComponent::class, ['endpointEvent' => $endpointEvent])
            ->call('replayDelivery', $delivery->id);

        Queue::assertNothingPushed();
    }
}
