<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire\Forms;

use Livewire\Form;

class CreateEndpointForm extends Form
{
    public string $name = '';

    public string $provider = '';

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
        ];
    }
}
