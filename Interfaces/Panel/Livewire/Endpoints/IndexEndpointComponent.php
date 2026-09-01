<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire\Endpoints;

use App\Routing\WebRoute;
use Domain\Endpoint\Actions\StoreEndpoint;
use Domain\Endpoint\Data\StoreEndpointData;
use Illuminate\View\View;
use Interfaces\Panel\Livewire\Forms\CreateEndpointForm;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.panel')]
class IndexEndpointComponent extends Component
{
    public CreateEndpointForm $form;

    public bool $showCreateModal = false;

    public function render(): View
    {
        return view('panel.endpoints.index', [
            'endpoints' => user()->endpoints()
                ->withCount('endpointEvents')
                ->latest()
                ->get(),
        ]);
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->form->reset();
        $this->resetValidation();
    }

    public function storeEndpoint(StoreEndpoint $storeEndpoint): void
    {
        $this->form->validate();

        $endpoint = $storeEndpoint->handle(new StoreEndpointData(
            userId: user()->id,
            name: $this->form->name,
            provider: $this->form->provider !== '' ? $this->form->provider : null,
        ));

        $this->redirect(route(WebRoute::ShowEndpoints, $endpoint));
    }
}
