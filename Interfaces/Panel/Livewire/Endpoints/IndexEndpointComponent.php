<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire\Endpoints;

use App\Routing\WebRoute;
use Domain\Endpoint\Actions\CreateEndpoint;
use Domain\Endpoint\Data\CreateEndpointData;
use Illuminate\View\View;
use Interfaces\Panel\Livewire\Forms\CreateEndpointForm;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.panel')]
class IndexEndpointComponent extends Component
{
    public CreateEndpointForm $form;

    public function render(): View
    {
        return view('panel.endpoints.index', [
            'endpoints' => user()->endpoints()
                ->withCount('endpointEvents')
                ->latest()
                ->get(),
        ]);
    }

    public function createEndpoint(CreateEndpoint $createEndpoint): void
    {
        $this->form->validate();

        $endpoint = $createEndpoint->handle(new CreateEndpointData(
            userId: user()->id,
            name: $this->form->name,
            provider: $this->form->provider !== '' ? $this->form->provider : null,
        ));

        $this->redirect(route(WebRoute::ShowEndpoints, $endpoint));
    }
}
