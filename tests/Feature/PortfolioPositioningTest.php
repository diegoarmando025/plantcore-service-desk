<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortfolioPositioningTest extends TestCase
{
    public function test_reviewer_guide_focuses_on_working_capabilities_and_next_steps(): void
    {
        $html = file_get_contents(resource_path('views/portfolio/guide.blade.php'));
        $this->assertStringContainsString('Conocer DevAlejandre', $html);
        $this->assertStringContainsString('https://devalejandre.com', $html);
        $this->assertStringNotContainsString('No incluye aún suscripciones', $html);
    }
}
