<?php

declare(strict_types=1);

namespace Domain\Delivery\Jobs;

use Cbox\Ssrf\Exceptions\BlockedUrl;
use Domain\Delivery\Data\DeliverySendResult;
use Domain\Delivery\Enums\DeliveryAttemptResult;
use Domain\Delivery\Enums\DeliveryOutcome;
use Domain\Delivery\Enums\DeliveryStatus;
use Domain\Delivery\Models\Delivery;
use Domain\Delivery\Models\DeliveryAttempt;
use Domain\Delivery\Utility\ClassifyDeliveryAttempt;
use Domain\Delivery\Utility\OutboundSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverDelivery implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(): void
    {
        if (! $this->claimDelivery()) {
            return;
        }

        $delivery = $this->loadDelivery();
        $result = $this->send($delivery);

        $this->recordAttempt($delivery, $result);

        $this->applyOutcome($delivery, $result);
    }

    public function failed(?Throwable $exception): void
    {
        Delivery::query()
            ->whereKey($this->deliveryId)
            ->whereNotIn('status', [
                DeliveryStatus::Succeeded->value,
                DeliveryStatus::Dead->value,
            ])
            ->update([
                'status' => DeliveryStatus::Dead->value,
                'locked_at' => null,
                'next_attempt_at' => null,
                'last_error' => $exception?->getMessage() ?? 'Job failed',
            ]);
    }

    private function claimDelivery(): bool
    {
        return Delivery::query()
            ->whereKey($this->deliveryId)
            ->where('status', DeliveryStatus::Pending->value)
            ->where('next_attempt_at', '<=', now())
            ->update([
                'status' => DeliveryStatus::InFlight->value,
                'locked_at' => now(),
                'attempts' => DB::raw('attempts + 1'),
            ]) === 1;
    }

    private function loadDelivery(): Delivery
    {
        return Delivery::query()
            ->with([
                'destination.unexpiredSigningSecrets',
                'endpointEvent',
            ])
            ->findOrFail($this->deliveryId);
    }

    private function send(Delivery $delivery): DeliverySendResult
    {
        $destination = $delivery->destination;
        $endpointEvent = $delivery->endpointEvent;
        $startedAt = microtime(true);
        $body = $endpointEvent->payload;

        $requestHeaders = OutboundSignature::headers(
            deliveryId: $delivery->id,
            sentAtUnix: now()->timestamp,
            body: $body,
            signingSecrets: $destination->unexpiredSigningSecrets,
            capturedHeaders: $endpointEvent->headers,
            destinationHeaders: $destination->headers ?? [],
        );

        try {
            $response = Http::ssrf()
                ->withHeaders($requestHeaders)
                ->timeout($destination->timeout_seconds)
                ->connectTimeout(min(5, $destination->timeout_seconds))
                ->withBody($body, $requestHeaders['content-type'] ?? 'application/json')
                ->post($destination->url);

            return new DeliverySendResult(
                requestHeaders: $requestHeaders,
                durationMs: $this->durationMs($startedAt),
                attemptResult: ClassifyDeliveryAttempt::fromHttpStatus($response->status()),
                responseStatus: $response->status(),
                responseBodySnippet: $this->trimResponse($response->body()),
                error: null,
            );
        } catch (BlockedUrl $blockedUrl) {
            return new DeliverySendResult(
                requestHeaders: $requestHeaders,
                durationMs: $this->durationMs($startedAt),
                attemptResult: DeliveryAttemptResult::Blocked,
                responseStatus: null,
                responseBodySnippet: null,
                error: $blockedUrl->getMessage(),
            );
        } catch (ConnectionException $connectionException) {
            return new DeliverySendResult(
                requestHeaders: $requestHeaders,
                durationMs: $this->durationMs($startedAt),
                attemptResult: DeliveryAttemptResult::ConnectionError,
                responseStatus: null,
                responseBodySnippet: null,
                error: $connectionException->getMessage(),
            );
        }
    }

    private function applyOutcome(Delivery $delivery, DeliverySendResult $result): void
    {
        match (ClassifyDeliveryAttempt::deliveryOutcome($result->attemptResult)) {
            DeliveryOutcome::Succeeded => $this->markSucceeded($delivery, $result->responseStatus),
            DeliveryOutcome::Failed => $this->markDead(
                $delivery,
                $result->responseStatus,
                $this->failureMessage($result),
            ),
            DeliveryOutcome::Retryable => $this->scheduleRetry($delivery, $result),
        };
    }

    private function recordAttempt(Delivery $delivery, DeliverySendResult $result): void
    {
        $attempt = new DeliveryAttempt();

        $attempt->delivery()->associate($delivery);
        $attempt->attempt_number = $delivery->attempts;
        $attempt->result = $result->attemptResult;
        $attempt->request_headers = $result->requestHeaders;
        $attempt->response_status = $result->responseStatus;
        $attempt->response_body_snippet = $result->responseBodySnippet;
        $attempt->duration_ms = $result->durationMs;
        $attempt->error = $result->error;

        $attempt->save();
    }

    private function markSucceeded(Delivery $delivery, ?int $responseStatus): void
    {
        $delivery->status = DeliveryStatus::Succeeded->value;
        $delivery->locked_at = null;
        $delivery->next_attempt_at = null;
        $delivery->last_status_code = $responseStatus;
        $delivery->last_error = null;
        $delivery->save();
    }

    private function markDead(Delivery $delivery, ?int $responseStatus, ?string $error): void
    {
        $delivery->status = DeliveryStatus::Dead->value;
        $delivery->locked_at = null;
        $delivery->next_attempt_at = null;
        $delivery->last_status_code = $responseStatus;
        $delivery->last_error = $error;
        $delivery->save();
    }

    private function scheduleRetry(Delivery $delivery, DeliverySendResult $result): void
    {
        if ($delivery->attempts >= $delivery->destination->max_attempts) {
            $this->markDead($delivery, $result->responseStatus, $this->failureMessage($result));

            return;
        }

        $delaySeconds = $this->backoffSeconds($delivery->attempts);
        $nextAttemptAt = now()->addSeconds($delaySeconds);

        $delivery->status = DeliveryStatus::Pending->value;
        $delivery->locked_at = null;
        $delivery->next_attempt_at = $nextAttemptAt->toDateTimeString();
        $delivery->last_status_code = $result->responseStatus;
        $delivery->last_error = $this->failureMessage($result);
        $delivery->save();

        self::dispatch($this->deliveryId)->delay($nextAttemptAt);
    }

    private function failureMessage(DeliverySendResult $result): ?string
    {
        if ($result->error !== null) {
            return $result->error;
        }

        if ($result->responseStatus === null) {
            return null;
        }

        return sprintf('HTTP %d', $result->responseStatus);
    }

    private function backoffSeconds(int $attemptNumber): int
    {
        $base = (int) config('hookline.delivery.backoff_base_seconds');
        $cap = (int) config('hookline.delivery.backoff_cap_seconds');
        $attemptNumber = max(1, $attemptNumber);

        $delay = min($cap, $base * (2 ** $attemptNumber - 1));

        return $delay + random_int(0, (int) floor($delay / 4));
    }

    private function trimResponse(string $body): string
    {
        $maxBytes = (int) config('hookline.delivery.response_snippet_bytes');
        if (strlen($body) <= $maxBytes) {
            return $body;
        }

        return substr($body, 0, $maxBytes);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
