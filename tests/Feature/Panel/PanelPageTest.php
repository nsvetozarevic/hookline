<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use Tests\TestCase;

class PanelPageTest extends TestCase
{
    public function test_placeholder_page_renders_with_built_stylesheet(): void
    {
        $this->get('/panel')
            ->assertOk()
            ->assertSee('Hookline', false)
            ->assertSee('rel="stylesheet"', false)
            ->assertSee('/build/assets/', false);
    }
}
