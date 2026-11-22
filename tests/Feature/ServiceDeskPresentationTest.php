<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Tests\TestCase;

class ServiceDeskPresentationTest extends TestCase
{
    public function test_status_and_priority_labels_have_complete_spanish_translations(): void
    {
        $this->assertSame(Ticket::STATUSES, array_keys(Ticket::STATUS_LABELS));
        $this->assertSame(Ticket::PRIORITIES, array_keys(Ticket::PRIORITY_LABELS));
        $ticket = new Ticket(['status' => 'in_progress', 'priority' => 'critical']);
        $this->assertSame('En proceso', $ticket->statusLabel());
        $this->assertSame('Crítica', $ticket->priorityLabel());
        $this->assertSame('text-bg-danger', $ticket->priorityBadgeClass());
        $ticket->status = 'resolved';
        $this->assertSame('Resuelta', $ticket->statusLabel());
        $this->assertSame('text-bg-success', $ticket->statusBadgeClass());
    }

    public function test_adminlte_layout_has_no_duplicated_global_title(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringNotContainsString('app-content-header', $layout);
        $this->assertStringContainsString('data-bs-theme="light"', $layout);
        $this->assertStringContainsString('adminlte.min.css', $layout);
    }

    public function test_ticket_detail_uses_adminlte_cards_and_preserves_real_actions(): void
    {
        $show = file_get_contents(resource_path('views/tickets/show.blade.php'));
        foreach ([
            'class="card shadow-sm', 'route(\'tickets.message\'',
            'route(\'tickets.update\'', 'route(\'tickets.transfer\'',
            'STATUS_LABELS', 'PRIORITY_LABELS', 'form-control',
            'form-select',
        ] as $fragment) {
            $this->assertStringContainsString($fragment, $show);
        }
    }
}
