<?php

namespace Interfaces\Inbound\Controllers;

use App\Http\Controllers\Controller;
use Domain\Endpoint\Actions\CaptureWebhook;
use Illuminate\Http\JsonResponse;
use Interfaces\Inbound\Requests\CaptureWebhookRequest;

class CaptureWebhookController extends Controller
{
    public function __invoke(CaptureWebhookRequest $request, CaptureWebhook $captureWebhook): JsonResponse
    {
        $captureWebhookResult = $captureWebhook->handle($request->captureWebhookData());

        return response()->json([
            'deduplication_key' => $captureWebhookResult->deduplicationKey,
        ], $captureWebhookResult->status);
    }
}
