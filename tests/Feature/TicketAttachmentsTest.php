<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\User;
use App\Support\PortfolioDemoScenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    private function setupTickets(): array
    {
        $department = Department::create(['code' => 'TI', 'name' => 'Tecnología']);
        $category = TicketCategory::create([
            'department_id' => $department->id, 'code' => 'INC',
            'name' => 'Incidencias', 'sla_hours' => 24,
        ]);
        $demo = User::create([
            'name' => 'Demo', 'email' => 'attachments-demo@example.invalid',
            'password' => 'A-private-testing-password!', 'role' => 'agent',
        ]);
        $demo->forceFill(['is_portfolio_demo' => true])->save();
        $agent = User::create([
            'name' => 'Agent', 'email' => 'attachments-agent@example.invalid',
            'password' => 'A-private-testing-password!', 'role' => 'agent',
        ]);
        DB::table('department_user')->insert([
            ['department_id' => $department->id, 'user_id' => $demo->id, 'role' => 'agent'],
            ['department_id' => $department->id, 'user_id' => $agent->id, 'role' => 'agent'],
        ]);
        $fake = Ticket::create([
            'title' => 'Demo with evidence', 'description' => 'A fictional incident for tests',
            'requester_id' => $demo->id, 'department_id' => $department->id,
            'ticket_category_id' => $category->id,
            'priority' => 'normal', 'status' => 'open', 'is_portfolio_demo' => true,
        ]);
        $private = Ticket::create([
            'title' => 'Private request', 'description' => 'Only for operational users',
            'requester_id' => $agent->id, 'department_id' => $department->id,
            'ticket_category_id' => $category->id,
            'priority' => 'normal', 'status' => 'open', 'is_portfolio_demo' => false,
        ]);
        return compact('demo', 'agent', 'fake', 'private');
    }

    public function test_demo_can_upload_download_and_view_private_evidence(): void
    {
        Storage::fake('local');
        extract($this->setupTickets());
        $this->actingAs($demo)->post("/tickets/{$fake->id}/attachments", [
            'attachment' => UploadedFile::fake()->createWithContent('reporte.txt', 'Evidencia ficticia'),
        ])->assertRedirect("/tickets/{$fake->id}");
        $attachment = TicketAttachment::where('ticket_id', $fake->id)->firstOrFail();
        $this->assertSame('reporte.txt', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);
        $this->actingAs($demo)->get("/tickets/{$fake->id}")->assertOk()
            ->assertSee('Evidencias adjuntas')->assertSee('reporte.txt');
        $this->actingAs($demo)->get("/tickets/{$fake->id}/attachments/{$attachment->id}")
            ->assertOk()->assertDownload('reporte.txt');
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $fake->id,
            'user_id' => $demo->id,
            'action' => 'attachment_added',
        ]);
    }

    public function test_attachment_cannot_cross_demo_or_real_tickets(): void
    {
        Storage::fake('local');
        extract($this->setupTickets());
        $path = 'ticket-attachments/'.$private->id.'/sensitive.txt';
        Storage::disk('local')->put($path, 'Operational secret');
        $attachment = $private->attachments()->create([
            'user_id' => $agent->id, 'path' => $path,
            'original_name' => 'sensitive.txt', 'mime_type' => 'text/plain', 'size' => 18,
        ]);
        $this->actingAs($demo)->get("/tickets/{$private->id}/attachments/{$attachment->id}")
            ->assertNotFound();
        $this->actingAs($demo)->get("/tickets/{$fake->id}/attachments/{$attachment->id}")
            ->assertNotFound();
        $this->actingAs($demo)->post("/tickets/{$private->id}/attachments", [
            'attachment' => UploadedFile::fake()->createWithContent('notes.txt', 'test'),
        ])->assertNotFound();
        $this->actingAs($agent)->get("/tickets/{$private->id}/attachments/{$attachment->id}")
            ->assertOk()->assertDownload('sensitive.txt');
        $this->actingAs($agent)->get("/tickets/{$fake->id}/attachments/{$attachment->id}")
            ->assertNotFound();
    }

    public function test_rejects_disallowed_extensions_oversized_files_and_closed_tickets(): void
    {
        Storage::fake('local');
        extract($this->setupTickets());
        $this->actingAs($demo)->post("/tickets/{$fake->id}/attachments", [
            'attachment' => UploadedFile::fake()->create('malicious.php', 2, 'application/x-php'),
        ])->assertSessionHasErrors('attachment');
        $this->actingAs($demo)->post("/tickets/{$fake->id}/attachments", [
            'attachment' => UploadedFile::fake()->create('oversize.pdf', 5200, 'application/pdf'),
        ])->assertSessionHasErrors('attachment');
        $fake->forceFill(['status' => 'closed'])->save();
        $this->actingAs($demo)->post("/tickets/{$fake->id}/attachments", [
            'attachment' => UploadedFile::fake()->createWithContent('notes.txt', 'test'),
        ])->assertStatus(409);
        $this->assertSame(0, TicketAttachment::count());
    }

    public function test_demo_reset_removes_fictional_files_but_preserves_operational_evidence(): void
    {
        Storage::fake('local');
        extract($this->setupTickets());
        // A demo reset needs the complete seeded catalog, so arrange it with
        // the preexisting scenario setup and its expected department codes.
        foreach ([
            ['RH', 'CONTRACT'], ['FIN', 'INVOICE'],
            ['COM', 'REQUISITION'], ['MTTO', 'REPAIR'],
            ['TI', 'DATABASE'], ['TI', 'NETWORK'], ['TI', 'APPLICATION'],
        ] as [$code, $categoryCode]) {
            $department = Department::firstOrCreate(['code' => $code], ['name' => $code]);
            TicketCategory::firstOrCreate(
                ['department_id' => $department->id, 'code' => $categoryCode],
                ['name' => $categoryCode, 'sla_hours' => 24]
            );
        }
        $fakePath = 'ticket-attachments/'.$fake->id.'/demo.txt';
        $realPath = 'ticket-attachments/'.$private->id.'/real.txt';
        Storage::disk('local')->put($fakePath, 'demo data');
        Storage::disk('local')->put($realPath, 'real data');
        $fake->attachments()->create([
            'user_id' => $demo->id, 'path' => $fakePath,
            'original_name' => 'demo.txt', 'mime_type' => 'text/plain', 'size' => 9,
        ]);
        $private->attachments()->create([
            'user_id' => $agent->id, 'path' => $realPath,
            'original_name' => 'real.txt', 'mime_type' => 'text/plain', 'size' => 9,
        ]);
        $this->assertSame(1, PortfolioDemoScenario::reset($demo));
        Storage::disk('local')->assertMissing($fakePath);
        Storage::disk('local')->assertExists($realPath);
        $this->assertDatabaseHas('ticket_attachments', ['path' => $realPath]);
        $this->assertDatabaseMissing('ticket_attachments', ['path' => $fakePath]);
    }

    public function test_guests_cannot_access_upload_or_download(): void
    {
        extract($this->setupTickets());
        $this->get("/tickets/{$fake->id}/attachments/1")->assertRedirect('/login');
        $this->post("/tickets/{$fake->id}/attachments", [])->assertRedirect('/login');
    }
}
