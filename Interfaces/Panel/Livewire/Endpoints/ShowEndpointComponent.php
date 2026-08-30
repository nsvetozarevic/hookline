<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire\Endpoints;

use App\Routing\WebRoute;
use Domain\Delivery\Actions\DeleteDestination;
use Domain\Delivery\Actions\StoreDestination;
use Domain\Delivery\Actions\UpdateDestination;
use Domain\Delivery\Data\StoreDestinationData;
use Domain\Delivery\Data\UpdateDestinationData;
use Domain\Delivery\Models\Destination;
use Domain\Endpoint\Actions\RotateEndpointSigningSecret;
use Domain\Endpoint\Models\Endpoint;
use Illuminate\View\View;
use Interfaces\Panel\Livewire\Forms\CreateDestinationForm;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panel')]
class ShowEndpointComponent extends Component
{
    use WithPagination;

    public Endpoint $endpoint;

    public CreateDestinationForm $form;

    public function mount(): void
    {
        $this->authorize('view', $this->endpoint);
    }

    public function render(): View
    {
        $this->endpoint->load('unexpiredSigningSecrets');

        $unexpiredSigningSecrets = $this->endpoint->unexpiredSigningSecrets;

        return view('panel.endpoints.show', [
            'captureUrl' => route(WebRoute::Capture, $this->endpoint->capture_token),
            'currentSigningSecret' => $unexpiredSigningSecrets->firstWhere('expires_at', null),
            'previousSigningSecrets' => $unexpiredSigningSecrets->whereNotNull('expires_at'),
            'endpointEvents' => $this->endpoint
                ->endpointEvents()
                ->latest()
                ->paginate(25),
            'destinations' => Destination::query()
                ->where('endpoint_id', $this->endpoint->id)
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function storeDestination(StoreDestination $storeDestination): void
    {
        $this->authorize('update', $this->endpoint);

        $this->form->validate();

        $storeDestination->handle(new StoreDestinationData(
            endpointId: $this->endpoint->id,
            url: $this->form->url,
        ));

        $this->form->reset();
    }

    public function updateDestination(int $destinationId, UpdateDestination $updateDestination): void
    {
        $this->authorize('update', $this->endpoint);

        $destination = Destination::query()
            ->whereKey($destinationId)
            ->where('endpoint_id', $this->endpoint->id)
            ->firstOrFail();

        $updateDestination->handle($destination, new UpdateDestinationData(
            isActive: ! $destination->is_active,
        ));
    }

    public function deleteDestination(int $destinationId, DeleteDestination $deleteDestination): void
    {
        $this->authorize('update', $this->endpoint);

        $destination = Destination::query()
            ->whereKey($destinationId)
            ->where('endpoint_id', $this->endpoint->id)
            ->firstOrFail();

        $deleteDestination->handle($destination);
    }

    public function rotateSigningSecret(RotateEndpointSigningSecret $rotateEndpointSigningSecret): void
    {
        $this->authorize('update', $this->endpoint);

        $rotateEndpointSigningSecret->handle($this->endpoint);
    }
}
