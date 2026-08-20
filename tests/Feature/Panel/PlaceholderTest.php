<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use Interfaces\Panel\Livewire\Placeholder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlaceholderTest extends TestCase
{
    #[Test]
    public function placeholder_component_renders(): void
    {
        Livewire::test(Placeholder::class)
            ->assertOk()
            ->assertSee('Hookline');
    }

    #[Test]
    public function placeholder_page_renders_with_built_stylesheet(): void
    {
        $this->get('/panel')
            ->assertOk()
            ->assertSee('Hookline', false)
            ->assertSee('rel="stylesheet"', false)
            ->assertSee('/build/assets/', false);
    }
}
