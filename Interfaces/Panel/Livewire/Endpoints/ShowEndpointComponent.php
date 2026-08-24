<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire\Endpoints;

use App\Routing\WebRoute;
use Domain\Endpoint\Models\Endpoint;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panel')]
class ShowEndpointComponent extends Component
{
    use WithPagination;

    public Endpoint $endpoint;

    public function mount(): void
    {
        $this->authorize('view', $this->endpoint);
    }

    public function render(): View
    {
        return view('panel.endpoints.show', [
            'captureUrl' => route(WebRoute::Capture, $this->endpoint->capture_token),
            'endpointEvents' => $this->endpoint
                ->endpointEvents()
                ->latest()
                ->paginate(25),
        ]);
    }
}
