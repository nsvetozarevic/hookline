<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function valid_credentials_return_a_plain_text_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route(ApiRoute::StoreToken, ['version' => 1]), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token']);

        $this->assertIsString($response->json('token'));
        $this->assertNotSame('', $response->json('token'));
        $this->assertGuest();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    #[Test]
    public function wrong_password_returns_unprocessable(): void
    {
        $user = User::factory()->create();

        $this->postJson(route(ApiRoute::StoreToken, ['version' => 1]), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function token_create_is_rate_limited(): void
    {
        $email = 'throttled@example.com';
        $throttleKey = Str::transliterate(sprintf('%s|%s', Str::lower($email), '127.0.0.1'));

        foreach (range(1, 5) as $ignored) {
            RateLimiter::hit(md5(sprintf('login%s', $throttleKey)));
        }

        $this->postJson(route(ApiRoute::StoreToken, ['version' => 1]), [
            'email' => $email,
            'password' => 'password',
        ])->assertTooManyRequests();
    }
}
