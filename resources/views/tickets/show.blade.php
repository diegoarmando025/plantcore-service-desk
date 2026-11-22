@extends('layouts.app')
@section('title', $ticket->code)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
 <div>
  <div class="small text-secondary mb-1">{{ $ticket->department->name }} / {{ $ticket->category->name }}</div>
  <h1 class="h4 fw-bold mb-1 text-body">{{ $ticket->title }}</h1>
  <div class="small text-secondary">Folio {{ $ticket->code }} · Registrado por {{ $ticket->requester->name === 'Evaluador PlantCore' ? 'Evaluador demo' : $ticket->requester->name }} el {{ $ticket->created_at->format('d/m/Y H:i') }}</div>
 </div>
 <a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}"><i class="bi bi-arrow-left me-1"></i>Volver a solicitudes</a>
</div>

<div class="card shadow-sm mb-3">
 <div class="card-body">
  <div class="row g-3 align-items-center">
   <div class="col-6 col-lg-2"><div class="text-secondary small mb-1">Estado</div><span class="badge {{ $ticket->statusBadgeClass() }}">{{ $ticket->statusLabel() }}</span></div>
   <div class="col-6 col-lg-2"><div class="text-secondary small mb-1">Prioridad</div><span class="badge {{ $ticket->priorityBadgeClass() }}">{{ $ticket->priorityLabel() }}</span></div>
   <div class="col-6 col-lg-3"><div class="text-secondary small mb-1">Responsable</div><strong class="small">{{ $ticket->assignee?->name === 'Evaluador PlantCore' ? 'Evaluador demo' : ($ticket->assignee?->name ?? 'Sin asignar') }}</strong></div>
   <div class="col-6 col-lg-2"><div class="text-secondary small mb-1">Equipo</div><strong class="small">{{ $ticket->team?->name ?? 'Sin equipo' }}</strong></div>
   <div class="col-12 col-lg-3"><div class="text-secondary small mb-1">Plazo de atención</div>
    <strong class="small {{ $ticket->due_at && $ticket->due_at->isPast() && !in_array($ticket->status,['resolved','closed']) ? 'text-danger' : '' }}">{{ $ticket->due_at?->format('d/m/Y H:i') ?? 'No definido' }}</strong>
   </div>
  </div>
 </div>
</div>

<div class="row g-3 align-items-start">
 <div class="col-12 col-xl-8">
  <div class="card shadow-sm mb-3">
   <div class="card-header"><h2 class="card-title fw-semibold"><i class="bi bi-card-text me-2"></i>Descripción</h2></div>
   <div class="card-body"><p class="mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $ticket->description }}</p></div>
  </div>
  <div class="card shadow-sm mb-3">
   <div class="card-header"><h2 class="card-title fw-semibold"><i class="bi bi-paperclip me-2"></i>Evidencias adjuntas</h2></div>
   <div class="card-body">
    @forelse($ticket->attachments as $attachment)
     <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom py-2">
      <div class="small"><i class="bi bi-file-earmark me-2"></i><span class="fw-semibold">{{ $attachment->original_name }}</span>
       <span class="text-secondary ms-2">{{ number_format($attachment->size / 1024, 1) }} KB · {{ $attachment->user?->name }}</span></div>
      <a class="btn btn-sm btn-outline-primary" href="{{ route('tickets.attachments.download',[$ticket,$attachment]) }}"><i class="bi bi-download me-1"></i>Descargar</a>
     </div>
    @empty<p class="text-secondary small">No se han adjuntado evidencias.</p>@endforelse
    @if($ticket->status !== 'closed')
     <form method="POST" action="{{ route('tickets.attachments.store',$ticket) }}" enctype="multipart/form-data" class="mt-3">
      @csrf
      <label for="evidence_file" class="form-label fw-semibold">Adjuntar evidencia</label>
      <input class="form-control" id="evidence_file" name="attachment" type="file" accept=".pdf,.png,.jpg,.jpeg,.txt" required>
      <p class="form-text mb-2">PDF, PNG, JPG o TXT · Hasta 5 MB por archivo · Máximo 12 por ticket</p>
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i>Subir archivo</button>
     </form>
    @endif
   </div>
  </div>
  <div class="card shadow-sm">
   <div class="card-header"><h2 class="card-title fw-semibold"><i class="bi bi-chat-square-text me-2"></i>Conversación</h2></div>
   <div class="card-body">
    @forelse($ticket->messages as $message)
     <div class="border rounded p-3 mb-3 {{ $message->internal ? 'bg-warning-subtle border-warning-subtle' : 'bg-body-tertiary' }}">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
       <div><strong>{{ $message->user->name === 'Evaluador PlantCore' ? 'Evaluador demo' : $message->user->name }}</strong> @if($message->internal)<span class="badge text-bg-warning ms-1">Nota interna</span>@endif</div>
       <span class="small text-secondary">{{ $message->created_at->format('d/m/Y H:i') }}</span>
      </div>
      <p class="mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $message->body }}</p>
     </div>
    @empty<p class="text-secondary">Todavía no se han registrado respuestas.</p>@endforelse
    @if($ticket->status !== 'closed')
    <form method="POST" action="{{ route('tickets.message',$ticket) }}" class="border-top pt-3 mt-3">
     @csrf
     <label for="reply_body" class="form-label fw-semibold">Publicar actualización</label>
     <textarea class="form-control mb-3" id="reply_body" name="body" rows="4" minlength="2" maxlength="10000" placeholder="Escribe una actualización..." required>{{ old('body') }}</textarea>
     @if($isAgent)<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="internal" value="1" id="reply_internal"><label class="form-check-label" for="reply_internal">Nota interna (no visible para el solicitante)</label></div>@endif
     <button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Publicar respuesta</button>
    </form>
    @endif
   </div>
  </div>
 </div>
 <div class="col-12 col-xl-4">
  <div class="card shadow-sm mb-3">
   <div class="card-header"><h2 class="card-title fw-semibold"><i class="bi bi-person-check me-2"></i>Gestión del ticket</h2></div>
   <div class="card-body">
    @if($isAgent && $ticket->status !== 'closed')
    <form method="POST" action="{{ route('tickets.update',$ticket) }}">
     @csrf @method('PUT')
     <div class="mb-3"><label class="form-label" for="ticket_status">Estado</label><select class="form-select" name="status" id="ticket_status">@foreach(\App\Models\Ticket::STATUSES as $status)<option value="{{ $status }}" @selected($ticket->status===$status)>{{ \App\Models\Ticket::STATUS_LABELS[$status] }}</option>@endforeach</select></div>
     <div class="mb-3"><label class="form-label" for="ticket_priority">Prioridad</label><select class="form-select" name="priority" id="ticket_priority">@foreach(\App\Models\Ticket::PRIORITIES as $priority)<option value="{{ $priority }}" @selected($ticket->priority===$priority)>{{ \App\Models\Ticket::PRIORITY_LABELS[$priority] }}</option>@endforeach</select></div>
     <div class="mb-3"><label class="form-label" for="ticket_team">Equipo</label><select class="form-select" name="team_id" id="ticket_team"><option value="">Sin equipo</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected($ticket->team_id===$team->id)>{{ $team->name }}</option>@endforeach</select></div>
     <div class="mb-3"><label class="form-label" for="ticket_assignee">Asignar a</label><select class="form-select" name="assignee_id" id="ticket_assignee"><option value="">Sin asignar</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($ticket->assignee_id===$user->id)>{{ $user->name }}</option>@endforeach</select></div>
     <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2 me-1"></i>Guardar cambios</button>
    </form>
    @else
     <dl class="row mb-0 small"><dt class="col-5 text-secondary">Estado</dt><dd class="col-7">{{ $ticket->statusLabel() }}</dd><dt class="col-5 text-secondary">Responsable</dt><dd class="col-7">{{ $ticket->assignee?->name === 'Evaluador PlantCore' ? 'Evaluador demo' : ($ticket->assignee?->name ?? 'Sin asignar') }}</dd></dl>
    @endif
   </div>
  </div>
  @if($isAgent && $ticket->status !== 'closed')
  <div class="card shadow-sm mb-3">
   <div class="card-header"><h2 class="card-title fw-semibold"><i class="bi bi-arrow-left-right me-2"></i>Transferir solicitud</h2></div>
   <div class="card-body">
    <p class="small text-secondary">Conserva el folio y las respuestas públicas. Las notas internas del área anterior permanecen privadas.</p>
    <form method="POST" action="{{ route('tickets.transfer',$ticket) }}">
     @csrf
     <div class="mb-3"><label class="form-label" for="transfer_department">Departamento destino</label><select class="form-select" id="transfer_department" name="department_id" required><option value="">Seleccionar</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
     <div class="mb-3"><label class="form-label" for="transfer_category">Servicio destino</label><select class="form-select" id="transfer_category" name="ticket_category_id" required><option value="">Selecciona primero un departamento</option>@foreach($categories as $category)<option data-department="{{ $category->department_id }}" value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
     <div class="mb-3"><label class="form-label" for="transfer_reason">Motivo</label><textarea class="form-control" name="reason" id="transfer_reason" rows="3" minlength="10" maxlength="1000" required placeholder="Explica por qué se transfiere..."></textarea></div>
     <button class="btn btn-outline-primary w-100" type="submit">Transferir solicitud</button>
    </form>
   </div>
  </div>
  @endif
  @if($isAgent)
  <div class="card shadow-sm">
   <div class="card-header"><h2 class="card-title fw-semibold"><i class="bi bi-clock-history me-2"></i>Historial de actividad</h2></div>
   <div class="card-body">
    <div class="border-start ps-3">
    @forelse($ticket->activities->sortByDesc('id') as $activity)
     <div class="pb-3 mb-3 border-bottom"><strong class="small d-block">{{ $activity->user->name === 'Evaluador PlantCore' ? 'Evaluador demo' : $activity->user->name }}</strong><span class="small">{{ match($activity->action) { 'created' => 'Solicitud creada', 'updated' => 'Solicitud actualizada', 'status_changed' => 'Estado actualizado', 'assigned' => 'Responsable asignado', 'transferred' => 'Solicitud transferida', 'message_added' => 'Respuesta registrada', default => ucfirst(str_replace('_', ' ', $activity->action)) } }}</span><span class="small d-block text-secondary">{{ $activity->created_at->format('d/m/Y H:i') }}</span></div>
    @empty<p class="small text-secondary">Sin eventos.</p>@endforelse
    </div>
   </div>
  </div>
  @endif
 </div>
</div>
<script>
(function () {
 const department = document.getElementById('transfer_department'), category = document.getElementById('transfer_category');
 if(!department || !category) return;
 function filterCategories(){
  for(const option of category.options){
   if(!option.value) continue;
   const available = option.dataset.department === department.value;
   option.hidden = !available; option.disabled = !available;
  }
  if(category.selectedOptions[0]?.disabled) category.value = '';
 }
 department.addEventListener('change',filterCategories);filterCategories();
})();
</script>
@endsection
