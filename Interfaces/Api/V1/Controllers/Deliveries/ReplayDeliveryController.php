<?php

declare(strict_types=1);

namespace Interfaces\Api\V1\Controllers\Deliveries;

use Domain\Delivery\Actions\ReplayDelivery;
use Domain\Delivery\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Interfaces\Api\Contracts\Deliveries\ReplayDeliveryControllerContract;
use Interfaces\Api\V1\Requests\ReplayDeliveryRequest;

class ReplayDeliveryController implements ReplayDeliveryControllerContract
{
    public function __construct(private ReplayDelivery $replayDelivery)
    {
    }

    public function __invoke(string $version, Delivery $delivery, ReplayDeliveryRequest $request): JsonResponse
    {
        if (! $this->replayDelivery->handle($delivery)) {
            $delivery->refresh();

            throw ValidationException::withMessages([
                'status' => sprintf('Delivery cannot be replayed while %s.', $delivery->status->value),
            ]);
        }

        return new JsonResponse(status: 202);
    }
}
