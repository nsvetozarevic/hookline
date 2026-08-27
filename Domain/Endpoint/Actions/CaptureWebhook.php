<?php

declare(strict_types=1);

namespace Domain\Endpoint\Actions;

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
            DB::transaction(function () use ($endpoint, $captureWebhookData, $deduplicationKey): void {
                $endpointEvent = new EndpointEvent();
                $endpointEvent->endpoint()->associate($endpoint);
                $endpointEvent->deduplication_key = $deduplicationKey;
                $endpointEvent->headers = $captureWebhookData->capturedHeaders;
                $endpointEvent->payload = $captureWebhookData->rawRequestBody;
                $endpointEvent->save();
            });

            return CaptureWebhookResult::accepted($deduplicationKey);
        } catch (UniqueConstraintViolationException) {
            return CaptureWebhookResult::duplicate($deduplicationKey);
        }
    }
}
