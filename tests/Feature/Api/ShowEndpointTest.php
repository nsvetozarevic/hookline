<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use Domain\Endpoint\Models\Endpoint;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_show_an_endpoint(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create([
            'name' => 'Stripe',
            'provider' => 'stripe',
        ]);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::ShowEndpoints, ['version' => 1, 'endpoint' => $endpoint]))
            ->assertOk()
            ->assertExactJson([
                'id' => $endpoint->public_id,
                'name' => 'Stripe',
                'provider' => 'stripe',
                'capture_token' => $endpoint->capture_token,
                'signing_secret' => $endpoint->currentSigningSecret->secret,
                'is_active' => true,
                'created_at' => $endpoint->created_at?->toJSON(),
            ]);
    }

    #[Test]
    public function another_user_cannot_show_an_endpoint(): void
    {
        $endpoint = Endpoint::factory()->create();
        $token = User::factory()->create()->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::ShowEndpoints, ['version' => 1, 'endpoint' => $endpoint]))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_show_an_endpoint(): void
    {
        $endpoint = Endpoint::factory()->create();

        $this->getJson(route(ApiRoute::ShowEndpoints, ['version' => 1, 'endpoint' => $endpoint]))
            ->assertUnauthorized();
    }
}
