<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire\Auth;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Login extends Component
{
    public function render(): View
    {
        return view('panel.auth.login');
    }
}
