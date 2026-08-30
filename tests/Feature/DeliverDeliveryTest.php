<?php

declare(strict_types=1);

namespace Tests\Feature;

use Domain\Delivery\Enums\DeliveryAttemptResult;
use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Jobs\DeliverDelivery;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\DeliveryAttempt;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeliverDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const string DESTINATION_URL = 'https://8.8.8.8/webhook';

    #[Test]
    public function two_hundred_marks_delivery_succeeded_and_records_a_signed_attempt(): void
    {
        ['delivery' => $delivery] = $this->seedPendingDelivery();

        Http::fake([
            self::DESTINATION_URL => Http::response('{"ok":true}', 200),
        ]);

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Succeeded, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNull($delivery->locked_at);
        $this->assertNull($delivery->next_attempt_at);

        $this->assertSame(1, DeliveryAttempt::query()->where('delivery_id', $delivery->id)->count());
        $attempt = DeliveryAttempt::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $this->assertSame(DeliveryAttemptResult::Succeeded, $attempt->result);
        $this->assertSame((string) $delivery->id, $attempt->request_headers['webhook-id']);
        $this->assertStringStartsWith('v1,', $attempt->request_headers['webhook-signature']);

        Http::assertSent(function ($request) {
            return $request->url() === self::DESTINATION_URL
                && $request->body() === '{"ok":true}';
        });
    }

    #[Test]
    public function five_hundred_schedules_retry_and_redispatches_the_job(): void
    {
        $this->travelTo(now()->startOfSecond());

        ['delivery' => $delivery] = $this->seedPendingDelivery();

        Http::fake([
            self::DESTINATION_URL => Http::response('error', 500),
        ]);
        Queue::fake();

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->next_attempt_at);
        $this->assertTrue($delivery->next_attempt_at > now());

        Queue::assertPushed(DeliverDelivery::class, fn (DeliverDelivery $job): bool => $job->deliveryId === $delivery->id);
    }

    #[Test]
    public function four_hundred_marks_delivery_dead_without_redispatch(): void
    {
        ['delivery' => $delivery] = $this->seedPendingDelivery();

        Http::fake([
            self::DESTINATION_URL => Http::response('bad request', 400),
        ]);
        Queue::fake();

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Dead, $delivery->status);
        $this->assertSame(400, $delivery->last_status_code);

        $attempt = DeliveryAttempt::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $this->assertSame(DeliveryAttemptResult::Failed, $attempt->result);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function four_twenty_nine_is_retryable(): void
    {
        ['delivery' => $delivery] = $this->seedPendingDelivery();

        Http::fake([
            self::DESTINATION_URL => Http::response('slow down', 429),
        ]);
        Queue::fake();

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        Queue::assertPushed(DeliverDelivery::class);
    }

    #[Test]
    public function four_zero_eight_is_retryable(): void
    {
        ['delivery' => $delivery] = $this->seedPendingDelivery();

        Http::fake([
            self::DESTINATION_URL => Http::response('timeout', 408),
        ]);
        Queue::fake();

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        Queue::assertPushed(DeliverDelivery::class);
    }

    #[Test]
    public function connection_exception_is_retryable_and_records_error(): void
    {
        ['delivery' => $delivery] = $this->seedPendingDelivery();

        Http::fake(function (): never {
            throw new ConnectionException('Connection refused');
        });
        Queue::fake();

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);

        $attempt = DeliveryAttempt::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $this->assertSame(DeliveryAttemptResult::ConnectionError, $attempt->result);
        $this->assertSame('Connection refused', $attempt->error);
        Queue::assertPushed(DeliverDelivery::class);
    }

    #[Test]
    public function five_hundred_at_max_attempts_marks_delivery_dead(): void
    {
        ['delivery' => $delivery, 'destination' => $destination] = $this->seedPendingDelivery(
            attempts: 7,
            maxAttempts: 8,
        );

        Http::fake([
            self::DESTINATION_URL => Http::response('error', 500),
        ]);
        Queue::fake();

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Dead, $delivery->status);
        $this->assertSame(8, $delivery->attempts);
        $this->assertSame(8, $destination->max_attempts);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function second_worker_loses_the_claim_race(): void
    {
        ['delivery' => $delivery] = $this->seedPendingDelivery();

        Http::fake([
            self::DESTINATION_URL => Http::response('{"ok":true}', 200),
        ]);
        Queue::fake();

        $this->runJob($delivery);
        $this->runJob($delivery);

        $this->assertSame(1, DeliveryAttempt::query()->where('delivery_id', $delivery->id)->count());
        Http::assertSentCount(1);
    }

    #[Test]
    public function not_yet_due_delivery_is_not_claimed(): void
    {
        ['delivery' => $delivery] = $this->seedPendingDelivery(
            nextAttemptAt: now()->addHour(),
        );

        Http::fake();
        Queue::fake();

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        $this->assertSame(0, $delivery->attempts);
        $this->assertDatabaseCount('delivery_attempts', 0);
        Http::assertNothingSent();
    }

    #[Test]
    public function ssrf_blocked_url_marks_delivery_dead_without_http(): void
    {
        ['delivery' => $delivery] = $this->seedPendingDelivery(
            url: 'http://169.254.169.254/latest/meta-data',
        );

        Http::fake(function (): never {
            $this->fail('HTTP must not be called for blocked URLs.');
        });
        Queue::fake();

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Dead, $delivery->status);
        $this->assertSame(1, DeliveryAttempt::query()->where('delivery_id', $delivery->id)->count());

        $attempt = DeliveryAttempt::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $this->assertSame(DeliveryAttemptResult::Blocked, $attempt->result);

        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    #[Test]
    public function failed_marks_in_flight_delivery_dead(): void
    {
        ['delivery' => $delivery] = $this->seedPendingDelivery();

        $delivery->status = DeliveryStatus::InFlight->value;
        $delivery->attempts = 1;
        $delivery->locked_at = now()->toDateTimeString();
        $delivery->save();

        (new DeliverDelivery($delivery->id))->failed(new \RuntimeException('Unexpected bug'));

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Dead, $delivery->status);
        $this->assertNull($delivery->locked_at);
        $this->assertNull($delivery->next_attempt_at);
        $this->assertSame('Unexpected bug', $delivery->last_error);
    }

    /**
     * @return array{endpointEvent: EndpointEvent, destination: Destination, delivery: Delivery}
     */
    private function seedPendingDelivery(
        ?string $url = null,
        int $attempts = 0,
        ?int $maxAttempts = null,
        ?\Illuminate\Support\Carbon $nextAttemptAt = null,
    ): array {
        $endpointEvent = EndpointEvent::factory()->create([
            'headers' => ['content-type' => 'application/json'],
            'payload' => '{"ok":true}',
        ]);

        $destination = Destination::factory()->create([
            'url' => $url ?? self::DESTINATION_URL,
            'max_attempts' => $maxAttempts ?? (int) config('hookline.delivery.default_max_attempts'),
        ]);

        $delivery = Delivery::factory()->create([
            'endpoint_event_id' => $endpointEvent->id,
            'destination_id' => $destination->id,
            'status' => DeliveryStatus::Pending,
            'attempts' => $attempts,
            'next_attempt_at' => $nextAttemptAt ?? now(),
        ]);

        return [
            'endpointEvent' => $endpointEvent,
            'destination' => $destination,
            'delivery' => $delivery,
        ];
    }

    private function runJob(Delivery $delivery): void
    {
        (new DeliverDelivery($delivery->id))->handle();
    }
}
