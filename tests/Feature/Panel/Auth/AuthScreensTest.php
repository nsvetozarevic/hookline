<?php

declare(strict_types=1);

namespace Tests\Feature\Panel\Auth;

use App\Routing\WebRoute;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthScreensTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_page_renders_the_form(): void
    {
        $this->get(route(WebRoute::ShowLogin))
            ->assertOk()
            ->assertSee('Log in', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('action="'.route(WebRoute::Login).'"', false);
    }

    #[Test]
    public function register_page_renders_the_form(): void
    {
        $this->get(route(WebRoute::ShowRegister))
            ->assertOk()
            ->assertSee('Register', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('action="'.route(WebRoute::Register).'"', false);
    }

    #[Test]
    public function authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route(WebRoute::ShowLogin))
            ->assertRedirect(route(WebRoute::IndexEndpoints));
    }
}
