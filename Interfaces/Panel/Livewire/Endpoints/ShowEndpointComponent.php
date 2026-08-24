<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire\Endpoints;

use App\Routing\WebRoute;
use Domain\Endpoint\Models\Endpoint;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.panel')]
class ShowEndpointComponent extends Component
{
    public Endpoint $endpoint;

    public function render(): View
    {
        return view('panel.endpoints.show', [
            'captureUrl' => route(WebRoute::Capture, $this->endpoint->capture_token),
        ]);
    }
}
