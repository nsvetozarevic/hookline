<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Routing\ApiRoute;
use Domain\Delivery\Enums\DeliveryAttemptResult;
use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\DeliveryAttempt;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Models\EndpointEvent;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowDeliveryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_show_a_delivery_with_attempt_log(): void
    {
        $user = User::factory()->create();
        $endpoint = Endpoint::factory()->for($user)->create();
        $destination = Destination::factory()->for($endpoint)->create([
            'url' => 'https://example.test/hooks',
        ]);
        $endpointEvent = EndpointEvent::factory()->for($endpoint)->create();
        $delivery = Delivery::factory()->for($endpointEvent)->for($destination)->create([
            'status' => DeliveryStatus::Succeeded,
            'attempts' => 2,
            'last_status_code' => 200,
            'last_error' => null,
            'next_attempt_at' => null,
        ]);
        $firstAttempt = DeliveryAttempt::factory()->for($delivery)->create([
            'attempt_number' => 1,
            'result' => DeliveryAttemptResult::Retryable,
            'response_status' => 500,
            'response_body_snippet' => 'error',
            'duration_ms' => 11,
            'error' => 'HTTP 500',
        ]);
        $secondAttempt = DeliveryAttempt::factory()->for($delivery)->create([
            'attempt_number' => 2,
            'result' => DeliveryAttemptResult::Succeeded,
            'response_status' => 200,
            'response_body_snippet' => '{"ok":true}',
            'duration_ms' => 8,
            'error' => null,
        ]);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::ShowDeliveries, ['version' => 1, 'delivery' => $delivery]))
            ->assertOk()
            ->assertExactJson([
                'id' => $delivery->public_id,
                'status' => 'succeeded',
                'attempts' => 2,
                'last_status_code' => 200,
                'destination_url' => 'https://example.test/hooks',
                'last_error' => null,
                'next_attempt_at' => null,
                'created_at' => $delivery->created_at?->toJSON(),
                'event_id' => $endpointEvent->public_id,
                'delivery_attempts' => [
                    [
                        'attempt_number' => 1,
                        'result' => 'retryable',
                        'response_status' => 500,
                        'response_body_snippet' => 'error',
                        'duration_ms' => 11,
                        'error' => 'HTTP 500',
                        'created_at' => $firstAttempt->created_at?->toJSON(),
                    ],
                    [
                        'attempt_number' => 2,
                        'result' => 'succeeded',
                        'response_status' => 200,
                        'response_body_snippet' => '{"ok":true}',
                        'duration_ms' => 8,
                        'error' => null,
                        'created_at' => $secondAttempt->created_at?->toJSON(),
                    ],
                ],
            ])
            ->assertJsonMissing(['secret' => $destination->currentSigningSecret->secret]);
    }

    #[Test]
    public function another_user_cannot_show_a_delivery(): void
    {
        $delivery = Delivery::factory()->create();
        $token = User::factory()->create()->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route(ApiRoute::ShowDeliveries, ['version' => 1, 'delivery' => $delivery]))
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_show_a_delivery(): void
    {
        $delivery = Delivery::factory()->create();

        $this->getJson(route(ApiRoute::ShowDeliveries, ['version' => 1, 'delivery' => $delivery]))
            ->assertUnauthorized();
    }
}
