<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_installs_without_accounts_and_is_idempotent(): void
    {
        $this->artisan('service-desk:install-catalog')->assertSuccessful();
        $this->assertSame(5, Department::count());
        $this->assertSame(0, User::count());
        $this->assertGreaterThan(10, TicketCategory::count());
        $this->artisan('service-desk:install-catalog')->assertSuccessful();
        $this->assertSame(5, Department::count());
        $this->assertSame(0, User::count());
    }
}
