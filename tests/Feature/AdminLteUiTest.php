<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminLteUiTest extends TestCase
{
    public function test_login_uses_bundled_adminlte_assets(): void
    {
        $response = $this->get('/login');
        $response->assertOk()
            ->assertSee('assets/vendor/adminlte.min.css')
            ->assertSee('assets/vendor/bootstrap.min.css')
            ->assertSee('assets/vendor/bootstrap-icons.css')
            ->assertSee('Correo corporativo');
    }

    public function test_adminlte_assets_are_committed_for_hostinger(): void
    {
        foreach ([
            'adminlte.min.css',
            'adminlte.min.js',
            'bootstrap.min.css',
            'bootstrap.bundle.min.js',
            'bootstrap-icons.css',
            'fonts/bootstrap-icons.woff2',
        ] as $file) {
            $this->assertFileExists(base_path('deploy/hostinger-public/assets/vendor/'.$file));
        }
    }

    public function test_authenticated_layout_uses_native_adminlte_without_conflicting_legacy_styles(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));
        $inbox = file_get_contents(resource_path('views/tickets/index.blade.php'));
        $create = file_get_contents(resource_path('views/tickets/create.blade.php'));

        $this->assertStringNotContainsString("asset('assets/app.css')", $layout);
        $this->assertStringContainsString('data-bs-theme="light"', $layout);
        $this->assertStringContainsString('?v=adminlte-fix-20261122', $layout);
        $this->assertStringContainsString('small-box text-bg-', $dashboard);
        $this->assertStringContainsString('class="card', $dashboard);
        $this->assertStringContainsString('class="card', $inbox);
        $this->assertStringContainsString('class="table table-hover', $inbox);
        $this->assertStringContainsString('class="card card-primary card-outline', $create);
        $this->assertStringContainsString('class="form-control"', $create);
    }

    public function test_public_and_hostinger_stylesheets_are_identical(): void
    {
        $this->assertSame(
            file_get_contents(public_path('assets/adminlte-overrides.css')),
            file_get_contents(base_path('deploy/hostinger-public/assets/adminlte-overrides.css'))
        );
    }

}
