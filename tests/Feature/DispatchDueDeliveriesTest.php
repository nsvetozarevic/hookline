<?php

declare(strict_types=1);

namespace Tests\Feature;

use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Jobs\DeliverDelivery;
use Domain\Delivery\Models\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DispatchDueDeliveriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_dispatches_jobs_for_pending_deliveries_that_are_due(): void
    {
        $this->travelTo(now()->startOfSecond());

        $due = Delivery::factory()->create([
            'next_attempt_at' => now(),
        ]);
        Queue::fake();

        $this->artisan('hookline:dispatch-due-deliveries')
            ->expectsOutput('Dispatched 1 due deliveries.')
            ->assertSuccessful();

        Queue::assertPushed(DeliverDelivery::class, fn (DeliverDelivery $job): bool => $job->deliveryId === $due->id);
    }

    #[Test]
    public function command_skips_pending_deliveries_that_are_not_yet_due(): void
    {
        Delivery::factory()->create([
            'next_attempt_at' => now()->addHour(),
        ]);
        Queue::fake();

        $this->artisan('hookline:dispatch-due-deliveries')
            ->expectsOutput('Dispatched 0 due deliveries.')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function command_skips_non_pending_deliveries(): void
    {
        $this->travelTo(now()->startOfSecond());

        Delivery::factory()->create([
            'status' => DeliveryStatus::InFlight,
            'next_attempt_at' => now(),
            'locked_at' => now(),
        ]);
        Delivery::factory()->create([
            'status' => DeliveryStatus::Succeeded,
            'next_attempt_at' => now(),
        ]);
        Delivery::factory()->create([
            'status' => DeliveryStatus::Dead,
            'next_attempt_at' => now(),
        ]);
        Queue::fake();

        $this->artisan('hookline:dispatch-due-deliveries')
            ->expectsOutput('Dispatched 0 due deliveries.')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }
}
