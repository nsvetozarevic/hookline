<?php

declare(strict_types=1);

namespace Interfaces\Inbound\Requests;

use Domain\Endpoint\Data\CaptureWebhookData;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Utility\WebhookSignatureVerifier;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Gates the public capture POST. Failed `rules()` always become 422, which is the
 * wrong signal for a webhook sender: oversize is 413, bad HMAC is 401, unknown
 * token is 404, event id over the column length is 400. Those checks live in
 * `withValidator()` and `abort()` with JSON.
 */
class CaptureWebhookRequest extends FormRequest
{
    private ?Endpoint $endpoint = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (): void {
            $this->endpoint = $this->resolveActiveEndpoint();

            $this->ensurePayloadIsWithinSizeLimit();
            $this->ensureSignatureIsValid();
            $this->ensureWebhookIdIsWithinLengthLimit();
        });
    }

    public function captureWebhookData(): CaptureWebhookData
    {
        return new CaptureWebhookData(
            endpoint: $this->endpoint(),
            rawRequestBody: $this->getContent(),
            capturedHeaders: $this->capturedHeaders(),
            webhookId: $this->header('webhook-id', ''),
        );
    }

    public function endpoint(): Endpoint
    {
        if ($this->endpoint === null) {
            throw new LogicException('Endpoint is only available after the capture request has been validated.');
        }

        return $this->endpoint;
    }

    /**
     * @return array<string, string>
     */
    private function capturedHeaders(): array
    {
        $capturedHeaders = [];

        foreach (config('hookline.capture.captured_header_names') as $headerName) {
            $headerValue = $this->header($headerName);

            if (is_string($headerValue) && $headerValue !== '') {
                $capturedHeaders[$headerName] = $headerValue;
            }
        }

        return $capturedHeaders;
    }

    private function resolveActiveEndpoint(): Endpoint
    {
        return Endpoint::query()
            ->with('unexpiredSigningSecrets')
            ->where('capture_token', $this->route('captureToken'))
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function ensurePayloadIsWithinSizeLimit(): void
    {
        $maximumBodyBytes = (int) config('hookline.capture.max_body_kilobytes') * 1024;

        if (strlen($this->getContent()) > $maximumBodyBytes) {
            abort(response()->json(['message' => 'Payload too large.'], 413));
        }
    }

    private function ensureSignatureIsValid(): void
    {
        $webhookSignatureVerifier = new WebhookSignatureVerifier();
        $webhookId = $this->header('webhook-id', '');
        $webhookTimestamp = $this->header('webhook-timestamp', '');
        $rawRequestBody = $this->getContent();
        $webhookSignature = $this->header('webhook-signature', '');
        $toleranceSeconds = (int) config('hookline.capture.timestamp_tolerance_seconds');

        foreach ($this->endpoint()->unexpiredSigningSecrets as $signingSecret) {
            $isValid = $webhookSignatureVerifier->verify(
                $signingSecret->secret,
                $webhookId,
                $webhookTimestamp,
                $rawRequestBody,
                $webhookSignature,
                $toleranceSeconds,
            );

            if ($isValid) {
                return;
            }
        }

        abort(response()->json(['message' => 'Invalid webhook signature.'], 401));
    }

    private function ensureWebhookIdIsWithinLengthLimit(): void
    {
        $maximumLength = (int) config('hookline.capture.max_deduplication_key_length');

        if (strlen($this->header('webhook-id', '')) > $maximumLength) {
            abort(response()->json(['message' => 'Event id too long.'], 400));
        }
    }
}
