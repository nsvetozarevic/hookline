<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.panel')]
class Placeholder extends Component
{
    public function render(): View
    {
        return view('panel.placeholder');
    }
}
