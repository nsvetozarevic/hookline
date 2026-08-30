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

                return (new FanOutDeliveries())->handle($endpointEvent);
            });

            foreach ($deliveryIds as $deliveryId) {
                DeliverDelivery::dispatch($deliveryId);
            }

            return CaptureWebhookResult::accepted($deduplicationKey);
        } catch (UniqueConstraintViolationException) {
            return CaptureWebhookResult::duplicate($deduplicationKey);
        }
    }
}
