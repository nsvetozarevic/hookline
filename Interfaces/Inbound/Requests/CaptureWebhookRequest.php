<?php

declare(strict_types=1);

namespace Interfaces\Inbound\Requests;

use Domain\Endpoint\Data\CaptureWebhookData;
use Domain\Endpoint\Models\Endpoint;
use Domain\Endpoint\Utility\HmacTimestampVerifier;
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
            $this->ensureHooklineEventIdIsWithinLengthLimit();
        });
    }

    public function captureWebhookData(): CaptureWebhookData
    {
        return new CaptureWebhookData(
            endpoint: $this->endpoint(),
            rawRequestBody: $this->getContent(),
            capturedHeaders: $this->capturedHeaders(),
            hooklineEventId: $this->hooklineEventId(),
        );
    }

    public function endpoint(): Endpoint
    {
        if ($this->endpoint === null) {
            throw new LogicException('Endpoint is only available after the capture request has been validated.');
        }

        return $this->endpoint;
    }

    private function hooklineEventId(): ?string
    {
        $hooklineEventId = $this->header('X-Hookline-Event-Id');

        if (! is_string($hooklineEventId) || $hooklineEventId === '') {
            return null;
        }

        return $hooklineEventId;
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
        $hmacTimestampVerifier = new HmacTimestampVerifier();

        $isValid = $hmacTimestampVerifier->verify(
            $this->endpoint()->signing_secret,
            (string) $this->header('X-Hookline-Timestamp', ''),
            $this->getContent(),
            (string) $this->header('X-Hookline-Signature', ''),
            (int) config('hookline.capture.timestamp_tolerance_seconds'),
        );

        if (! $isValid) {
            abort(response()->json(['message' => 'Invalid webhook signature.'], 401));
        }
    }

    private function ensureHooklineEventIdIsWithinLengthLimit(): void
    {
        $hooklineEventId = $this->hooklineEventId();

        if ($hooklineEventId === null) {
            return;
        }

        $maximumLength = (int) config('hookline.capture.max_deduplication_key_length');

        if (strlen($hooklineEventId) > $maximumLength) {
            abort(response()->json(['message' => 'Event id too long.'], 400));
        }
    }
}
