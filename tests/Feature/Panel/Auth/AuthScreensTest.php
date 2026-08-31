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
            ->assertSee('action="'.route(WebRoute::Login).'"', false);
    }

    #[Test]
    public function register_page_renders_the_form(): void
    {
        $this->get(route(WebRoute::ShowRegister))
            ->assertOk()
            ->assertSee('action="'.route(WebRoute::Register).'"', false);
    }

    #[Test]
    public function guest_visiting_home_is_redirected_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route(WebRoute::ShowLogin));
    }

    #[Test]
    public function authenticated_user_visiting_home_is_redirected_to_endpoints(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route(WebRoute::IndexEndpoints));
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
