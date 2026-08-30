<?php

declare(strict_types=1);

namespace Interfaces\Panel\Livewire\Forms;

use Cbox\Ssrf\Rules\PublicUrl;
use Livewire\Form;

class CreateDestinationForm extends Form
{
    public string $url = '';

    /**
     * @return array<string, list<PublicUrl|string>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', new PublicUrl(allowedSchemes: ['https'])],
        ];
    }
}
