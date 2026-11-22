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
}
