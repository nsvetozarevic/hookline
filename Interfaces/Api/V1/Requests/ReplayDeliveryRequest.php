<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Requests;

use Domain\Delivery\Models\Delivery;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

class ReplayDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user()->can('view', $this->delivery()->endpointEvent->endpoint);
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
        $validator->after(function () use ($validator): void {
            $delivery = $this->delivery();

            if ($delivery->status->isReplayable()) {
                return;
            }

            $validator->errors()->add(
                'status',
                sprintf('Delivery cannot be replayed while %s.', $delivery->status->value),
            );
        });
    }

    public function delivery(): Delivery
    {
        $delivery = $this->route('delivery');

        if (! $delivery instanceof Delivery) {
            throw new LogicException('Delivery is only available after route binding.');
        }

        return $delivery;
    }
}
