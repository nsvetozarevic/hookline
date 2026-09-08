<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyCurrentTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function current_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->deleteJson(route(ApiRoute::DestroyCurrentToken, ['version' => 1]))
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function guest_cannot_revoke_a_token(): void
    {
        $this->deleteJson(route(ApiRoute::DestroyCurrentToken, ['version' => 1]))
            ->assertUnauthorized();
    }
}
