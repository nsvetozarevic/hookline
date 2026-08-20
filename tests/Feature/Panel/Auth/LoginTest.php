<?php

declare(strict_types=1);

namespace Tests\Feature\Panel\Auth;

use App\Routing\WebRoute;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function valid_credentials_authenticate_and_redirect_to_endpoints(): void
    {
        $user = User::factory()->create();

        $this->post(route(WebRoute::Login), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route(WebRoute::EndpointsIndex));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function invalid_password_does_not_authenticate(): void
    {
        $user = User::factory()->create();

        $this->post(route(WebRoute::Login), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route(WebRoute::Logout))
            ->assertRedirect();

        $this->assertGuest();
    }

    #[Test]
    public function login_is_rate_limited(): void
    {
        $email = 'throttled@example.com';
        $throttleKey = Str::transliterate(Str::lower($email).'|127.0.0.1');

        foreach (range(1, 5) as $ignored) {
            RateLimiter::hit(md5('login'.$throttleKey));
        }

        $this->post(route(WebRoute::Login), [
            'email' => $email,
            'password' => 'password',
        ])->assertTooManyRequests();
    }
}
