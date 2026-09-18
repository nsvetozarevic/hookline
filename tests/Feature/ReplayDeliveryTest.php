<?php

declare(strict_types=1);

namespace Tests\Feature;

use Domain\Delivery\Actions\ReplayDelivery;
use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Jobs\DeliverDelivery;
use Domain\Delivery\Models\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReplayDeliveryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_replays_a_dead_delivery(): void
    {
        $this->travelTo(now()->startOfSecond());

        $delivery = Delivery::factory()->create([
            'status' => DeliveryStatus::Dead,
            'attempts' => 3,
            'locked_at' => now(),
            'last_status_code' => 500,
            'last_error' => 'boom',
        ]);
        Queue::fake();

        $this->assertTrue((new ReplayDelivery())->handle($delivery));

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        $this->assertSame(0, $delivery->attempts);
        $this->assertNull($delivery->locked_at);
        $this->assertTrue($delivery->next_attempt_at->equalTo(now()));
        $this->assertNull($delivery->last_status_code);
        $this->assertNull($delivery->last_error);

        Queue::assertPushed(DeliverDelivery::class, fn (DeliverDelivery $job): bool => $job->deliveryId === $delivery->id);
    }

    #[Test]
    public function it_ignores_stale_status_on_an_in_flight_delivery(): void
    {
        $delivery = Delivery::factory()->create([
            'status' => DeliveryStatus::InFlight,
            'attempts' => 2,
            'locked_at' => now(),
        ]);
        $delivery->status = DeliveryStatus::Dead;
        Queue::fake();

        $this->assertFalse((new ReplayDelivery())->handle($delivery));

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::InFlight, $delivery->status);
        $this->assertSame(2, $delivery->attempts);
        $this->assertNotNull($delivery->locked_at);

        Queue::assertNothingPushed();
    }
}
