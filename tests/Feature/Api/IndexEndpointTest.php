<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_lists_only_their_endpoints(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create([
            'name' => 'Stripe',
            'provider' => 'stripe',
        ]);
        Endpoint::factory()->create(['name' => 'Other']);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::IndexEndpoints, ['version' => 1]))
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    [
                        'id' => $endpoint->public_id,
                        'name' => 'Stripe',
                        'provider' => 'stripe',
                        'capture_token' => $endpoint->capture_token,
                        'is_active' => true,
                        'created_at' => $endpoint->created_at?->toJSON(),
                    ],
                ],
            ])
            ->assertJsonMissing(['secret' => $endpoint->currentSigningSecret->secret]);
    }

    #[Test]
    public function guest_cannot_list_endpoints(): void
    {
        $this->getJson(route(ApiRoute::IndexEndpoints, ['version' => 1]))
            ->assertUnauthorized();
    }
}
