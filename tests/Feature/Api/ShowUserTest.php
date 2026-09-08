<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_is_returned(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::ShowUser, ['version' => 1]))
            ->assertOk()
            ->assertExactJson([
                'id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    #[Test]
    public function guest_cannot_show_the_user(): void
    {
        $this->getJson(route(ApiRoute::ShowUser, ['version' => 1]))
            ->assertUnauthorized();
    }
}
