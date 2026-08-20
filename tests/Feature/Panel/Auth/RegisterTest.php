<?php

declare(strict_types=1);

namespace Tests\Feature\Panel\Auth;

use App\Routing\WebRoute;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function register_creates_a_user_and_authenticates(): void
    {
        $this->post(route(WebRoute::Register), $this->validPayload())->assertRedirect(route(WebRoute::EndpointsIndex));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Nikola',
            'email' => 'nikola@example.com',
        ]);
    }

    /**
     * @param  array<string, mixed>  $invalidData
     */
    #[DataProvider('invalidData')]
    #[Test]
    public function register_rejects_invalid_data(array $invalidData, string $fieldKey): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post(route(WebRoute::Register), array_merge($this->validPayload(), $invalidData))
            ->assertSessionHasErrors([$fieldKey]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidData(): array
    {
        return [
            'name - required' => [
                ['name' => ''],
                'name',
            ],
            'name - string' => [
                ['name' => ['not-a-string']],
                'name',
            ],
            'name - max:255' => [
                ['name' => str_repeat('a', 256)],
                'name',
            ],
            'email - required' => [
                ['email' => ''],
                'email',
            ],
            'email - email' => [
                ['email' => 'not-an-email'],
                'email',
            ],
            'email - max:255' => [
                ['email' => str_repeat('a', 244).'@example.com'],
                'email',
            ],
            'email - unique' => [
                ['email' => 'taken@example.com'],
                'email',
            ],
            'password - required' => [
                ['password' => '', 'password_confirmation' => ''],
                'password',
            ],
            'password - string' => [
                ['password' => ['not-a-string'], 'password_confirmation' => ['not-a-string']],
                'password',
            ],
            'password - confirmed' => [
                ['password' => 'password', 'password_confirmation' => 'different'],
                'password',
            ],
            'password - min:8' => [
                ['password' => 'short', 'password_confirmation' => 'short'],
                'password',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Nikola',
            'email' => 'nikola@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }
}
