<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexEndpointEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_lists_only_requested_endpoints_events(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create([
            'deduplication_key' => 'evt_visible',
            'payload' => '{"ok":true}',
        ]);
        EndpointEvent::factory()->create(['deduplication_key' => 'evt_other']);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson(route(ApiRoute::IndexEndpointEvents, ['version' => 1, 'endpoint' => $endpoint]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['payload' => '{"ok":true}']);

        $this->assertSame(
            [
                'id' => $endpointEvent->public_id,
                'deduplication_key' => 'evt_visible',
                'created_at' => $endpointEvent->created_at?->toJSON(),
            ],
            $response->json('data.0'),
        );
    }

    #[Test]
    public function events_are_paginated(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        EndpointEvent::factory()->for($endpoint)->count(26)->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::IndexEndpointEvents, [
                'version' => 1,
                'endpoint' => $endpoint,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 25)
            ->assertJsonPath('meta.total', 26);
    }

    #[Test]
    public function another_user_cannot_list_events(): void
    {
        $endpoint = Endpoint::factory()->create();
        $token = User::factory()->create()->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::IndexEndpointEvents, ['version' => 1, 'endpoint' => $endpoint]))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_list_events(): void
    {
        $endpoint = Endpoint::factory()->create();

        $this->getJson(route(ApiRoute::IndexEndpointEvents, ['version' => 1, 'endpoint' => $endpoint]))
            ->assertUnauthorized();
    }
}
