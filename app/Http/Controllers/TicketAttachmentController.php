<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Support\TicketAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TicketAttachmentController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        TicketAccess::view($request->user(), $ticket);
        abort_if($ticket->status === 'closed', 409, 'No se admiten evidencias en tickets cerrados.');

        $data = $request->validate([
            'attachment' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,txt'],
        ]);
        $file = $data['attachment'];
        $disk = Storage::disk('local');

        // Laravel's local disk uses storage/app/private in modern Laravel releases.
        // Never create a public storage link for this feature.
        $path = null;
        try {
            $attachment = DB::transaction(function () use ($request, $ticket, $file, $disk, &$path) {
                $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
                TicketAccess::view($request->user(), $locked);
                abort_if($locked->status === 'closed', 409, 'No se admiten evidencias en tickets cerrados.');

                abort_if($locked->attachments()->count() >= 12, 422, 'Máximo 12 evidencias por ticket.');
                if ($locked->is_portfolio_demo) {
                    abort_if(TicketAttachment::whereHas('ticket', fn ($q) => $q->where('is_portfolio_demo', true))->count() >= 100,
                        422, 'El escenario público alcanzó el límite de evidencias.');
                }

                $path = $file->store('ticket-attachments/'.$locked->id, 'local');
                if (! $path) {
                    throw new \RuntimeException('No fue posible almacenar la evidencia.');
                }
                $attachment = $locked->attachments()->create([
                    'user_id' => $request->user()->id,
                    'original_name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255),
                    'path' => $path,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                ]);
                $locked->activities()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'attachment_added',
                    'details' => ['attachment_id' => $attachment->id],
                ]);
                return $attachment;
            }, 3);
        } catch (\Throwable $e) {
            if ($path) $disk->delete($path);
            throw $e;
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('ok', 'Evidencia adjuntada correctamente.');
    }

    public function download(Request $request, Ticket $ticket, TicketAttachment $attachment): BinaryFileResponse
    {
        abort_unless($attachment->ticket_id === $ticket->id, 404);
        TicketAccess::view($request->user(), $ticket);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->path), 404);
        return response()->download(
            $disk->path($attachment->path),
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type, 'Cache-Control' => 'private, no-store']
        );
    }
}
