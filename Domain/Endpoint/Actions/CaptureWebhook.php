<?php

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
        $rawRequestBody = $captureWebhookData->rawRequestBody;
        $deduplicationKey = $this->deduplicationKey(
            $captureWebhookData->hooklineEventId,
            $rawRequestBody,
        );

        try {
            DB::transaction(function () use ($endpoint, $captureWebhookData, $rawRequestBody, $deduplicationKey): void {
                $endpointEvent = new EndpointEvent();
                $endpointEvent->endpoint()->associate($endpoint);
                $endpointEvent->deduplication_key = $deduplicationKey;
                $endpointEvent->headers = $captureWebhookData->capturedHeaders;
                $endpointEvent->payload = $rawRequestBody;
                $endpointEvent->received_at = now();
                $endpointEvent->save();
            });

            return CaptureWebhookResult::accepted($deduplicationKey);
        } catch (UniqueConstraintViolationException) {
            return CaptureWebhookResult::duplicate($deduplicationKey);
        }
    }

    private function deduplicationKey(?string $hooklineEventId, string $rawRequestBody): string
    {
        if ($hooklineEventId !== null) {
            return $hooklineEventId;
        }

        return hash('sha256', $rawRequestBody);
    }
}
