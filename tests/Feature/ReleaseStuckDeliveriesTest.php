<?php

declare(strict_types=1);

namespace Tests\Feature;

use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Models\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReleaseStuckDeliveriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_releases_in_flight_deliveries_stuck_past_the_timeout(): void
    {
        $this->travelTo(now()->startOfSecond());
        config(['hookline.delivery.in_flight_timeout_seconds' => 300]);

        $stuck = Delivery::factory()->create([
            'status' => DeliveryStatus::InFlight,
            'locked_at' => now()->subMinutes(10),
            'next_attempt_at' => now()->addHour(),
        ]);

        $this->artisan('hookline:release-stuck-deliveries')
            ->expectsOutput('Released 1 stuck deliveries.')
            ->assertSuccessful();

        $stuck->refresh();
        $this->assertSame(DeliveryStatus::Pending, $stuck->status);
        $this->assertNull($stuck->locked_at);
        $this->assertDatabaseHas('deliveries', [
            'id' => $stuck->id,
            'next_attempt_at' => now()->toDateTimeString(),
        ]);
    }

    #[Test]
    public function command_leaves_recent_in_flight_deliveries_alone(): void
    {
        $this->travelTo(now()->startOfSecond());
        config(['hookline.delivery.in_flight_timeout_seconds' => 300]);

        $recent = Delivery::factory()->create([
            'status' => DeliveryStatus::InFlight,
            'locked_at' => now()->subMinute(),
        ]);

        $this->artisan('hookline:release-stuck-deliveries')
            ->expectsOutput('Released 0 stuck deliveries.')
            ->assertSuccessful();

        $recent->refresh();
        $this->assertSame(DeliveryStatus::InFlight, $recent->status);
        $this->assertNotNull($recent->locked_at);
    }

    #[Test]
    public function command_ignores_non_in_flight_deliveries(): void
    {
        $this->travelTo(now()->startOfSecond());
        config(['hookline.delivery.in_flight_timeout_seconds' => 300]);

        Delivery::factory()->create([
            'status' => DeliveryStatus::Pending,
            'locked_at' => now()->subHour(),
        ]);
        Delivery::factory()->create([
            'status' => DeliveryStatus::Succeeded,
            'locked_at' => now()->subHour(),
        ]);
        Delivery::factory()->create([
            'status' => DeliveryStatus::Dead,
            'locked_at' => now()->subHour(),
        ]);

        $this->artisan('hookline:release-stuck-deliveries')
            ->expectsOutput('Released 0 stuck deliveries.')
            ->assertSuccessful();
    }
}
