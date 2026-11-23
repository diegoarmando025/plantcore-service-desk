<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PublicDemoWriteLimitsTest extends TestCase
{
    public function test_sensitive_mutation_routes_have_rate_limits(): void
    {
        $expected = [
            'tickets.store' => 'throttle:30,1',
            'tickets.update' => 'throttle:60,1',
            'tickets.transfer' => 'throttle:20,1',
            'tickets.message' => 'throttle:60,1',
            'tickets.attachments.store' => 'throttle:12,1',
            'admin.portfolio-demo.complete' => 'throttle:3,1',
            'admin.departments.store' => 'throttle:10,1',
            'admin.teams.store' => 'throttle:10,1',
            'admin.categories.store' => 'throttle:10,1',
            'admin.users.store' => 'throttle:5,1',
            'admin.memberships.store' => 'throttle:20,1',
            'admin.team-members.store' => 'throttle:20,1',
        ];

        foreach ($expected as $name => $limit) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing route: {$name}");
            $this->assertContains('auth', $route->gatherMiddleware(), "Unauthenticated write route: {$name}");
            $this->assertContains($limit, $route->gatherMiddleware(), "Missing write limit: {$name}");
        }
    }

    public function test_demo_ticket_creation_requires_authentication(): void
    {
        $this->post(route('tickets.store'), [])->assertRedirect(route('login'));
    }
}
