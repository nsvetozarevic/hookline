<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use Interfaces\Panel\Livewire\Placeholder;
use Livewire\Livewire;
use Tests\TestCase;

class PlaceholderTest extends TestCase
{
    public function test_placeholder_component_renders(): void
    {
        Livewire::test(Placeholder::class)
            ->assertOk()
            ->assertSee('Hookline');
    }

    public function test_placeholder_page_renders_with_built_stylesheet(): void
    {
        $this->get('/panel')
            ->assertOk()
            ->assertSee('Hookline', false)
            ->assertSee('rel="stylesheet"', false)
            ->assertSee('/build/assets/', false);
    }
}
