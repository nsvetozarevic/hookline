<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Jobs\DeliverDelivery;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReplayDeliveryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_replay_a_dead_delivery(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $destination = Destination::factory()->for($endpoint)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create();
        $delivery = Delivery::factory()->for($endpointEvent)->for($destination)->create([
            'status' => DeliveryStatus::Dead,
            'attempts' => 3,
        ]);
        $token = $user->createToken('api')->plainTextToken;
        Queue::fake();

        $this->withToken($token)
            ->postJson(route(ApiRoute::ReplayDeliveries, ['version' => 1, 'delivery' => $delivery]))
            ->assertAccepted();

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        $this->assertSame(0, $delivery->attempts);

        Queue::assertPushed(DeliverDelivery::class, fn (DeliverDelivery $job): bool => $job->deliveryId === $delivery->id);
    }

    #[Test]
    public function owner_cannot_replay_a_pending_delivery(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $destination = Destination::factory()->for($endpoint)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create();
        $delivery = Delivery::factory()->for($endpointEvent)->for($destination)->create([
            'status' => DeliveryStatus::Pending,
        ]);
        $token = $user->createToken('api')->plainTextToken;
        Queue::fake();

        $this->withToken($token)
            ->postJson(route(ApiRoute::ReplayDeliveries, ['version' => 1, 'delivery' => $delivery]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        Queue::assertNothingPushed();
    }

    #[Test]
    public function another_user_cannot_replay_a_delivery(): void
    {
        $delivery = Delivery::factory()->create([
            'status' => DeliveryStatus::Dead,
        ]);
        $token = User::factory()->create()->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson(route(ApiRoute::ReplayDeliveries, ['version' => 1, 'delivery' => $delivery]))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_replay_a_delivery(): void
    {
        $delivery = Delivery::factory()->create([
            'status' => DeliveryStatus::Dead,
        ]);

        $this->postJson(route(ApiRoute::ReplayDeliveries, ['version' => 1, 'delivery' => $delivery]))
            ->assertUnauthorized();
    }
}
