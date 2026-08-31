<?php

declare(strict_types=1);

namespace Domain\Endpoint\Actions;

use Domain\Delivery\Actions\FanOutDeliveries;
use Domain\Delivery\Jobs\DeliverDelivery;
use Domain\Endpoint\Data\CaptureWebhookData;
use Domain\Endpoint\Data\CaptureWebhookResult;
use Domain\Endpoint\Models\EndpointEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaptureWebhook
{
    public function handle(CaptureWebhookData $captureWebhookData): CaptureWebhookResult
    {
        $endpoint = $captureWebhookData->endpoint;
        $deduplicationKey = $captureWebhookData->webhookId;

        try {
            $deliveryIds = DB::transaction(function () use ($endpoint, $captureWebhookData, $deduplicationKey): array {
                $endpointEvent = new EndpointEvent();
                $endpointEvent->endpoint()->associate($endpoint);
                $endpointEvent->deduplication_key = $deduplicationKey;
                $endpointEvent->headers = $captureWebhookData->capturedHeaders;
                $endpointEvent->payload = $captureWebhookData->rawRequestBody;
                $endpointEvent->save();

                $deliveryIds = (new FanOutDeliveries())->handle($endpointEvent);

                Log::channel('hookline')->info('Capture accepted.', [
                    'endpoint_id' => $endpoint->id,
                    'event_id' => $endpointEvent->id,
                    'deduplication_key' => $deduplicationKey,
                    'delivery_count' => count($deliveryIds),
                    'payload_bytes' => strlen($captureWebhookData->rawRequestBody),
                ]);

                return $deliveryIds;
            });

            foreach ($deliveryIds as $deliveryId) {
                DeliverDelivery::dispatch($deliveryId);
            }

            return CaptureWebhookResult::accepted($deduplicationKey);
        } catch (UniqueConstraintViolationException) {
            Log::channel('hookline')->info('Capture duplicate.', [
                'endpoint_id' => $endpoint->id,
                'deduplication_key' => $deduplicationKey,
            ]);

            return CaptureWebhookResult::duplicate($deduplicationKey);
        }
    }
}
