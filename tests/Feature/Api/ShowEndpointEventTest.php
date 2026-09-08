<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowEndpointEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_show_an_event_with_delivery_summaries(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $destination = Destination::factory()->for($endpoint)->create([
            'url' => 'https://example.test/hooks',
        ]);
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'deduplication_key' => 'evt_visible',
            'headers' => ['content-type' => 'application/json'],
            'payload' => '{"ok":true}',
        ]);
        $delivery = Delivery::factory()->for($endpointEvent)->for($destination)->create([
            'status' => DeliveryStatus::Succeeded,
            'attempts' => 1,
            'last_status_code' => 200,
        ]);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::ShowEvents, ['version' => 1, 'endpointEvent' => $endpointEvent]))
            ->assertOk()
            ->assertExactJson([
                'id' => $endpointEvent->public_id,
                'deduplication_key' => 'evt_visible',
                'headers' => ['content-type' => 'application/json'],
                'payload' => '{"ok":true}',
                'created_at' => $endpointEvent->created_at?->toJSON(),
                'deliveries' => [
                    [
                        'id' => $delivery->public_id,
                        'status' => 'succeeded',
                        'attempts' => 1,
                        'last_status_code' => 200,
                        'destination_url' => 'https://example.test/hooks',
                    ],
                ],
            ])
            ->assertJsonMissing(['secret' => $destination->currentSigningSecret->secret]);
    }

    #[Test]
    public function another_user_cannot_show_an_event(): void
    {
        $endpointEvent = EndpointEvent::factory()->create();
        $token = User::factory()->create()->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::ShowEvents, ['version' => 1, 'endpointEvent' => $endpointEvent]))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_show_an_event(): void
    {
        $endpointEvent = EndpointEvent::factory()->create();

        $this->getJson(route(ApiRoute::ShowEvents, ['version' => 1, 'endpointEvent' => $endpointEvent]))
            ->assertUnauthorized();
    }
}
