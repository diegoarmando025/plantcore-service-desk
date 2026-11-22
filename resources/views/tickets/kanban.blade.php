@extends('layouts.app')
@section('title','Tablero Kanban')
@section('content')
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
 <div><h1 class="h4 fw-bold mb-1">Tablero de solicitudes</h1>
  <p class="text-secondary mb-0">Seguimiento por estado. Los cambios se guardan en MySQL y aparecen en el historial de cada ticket.</p></div>
 <div class="d-flex gap-2"><a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary"><i class="bi bi-list-ul me-1"></i>Ver bandeja</a>
  <a href="{{ route('tickets.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva solicitud</a></div>
</div>
<p class="small text-secondary mb-3"><i class="bi bi-info-circle me-1"></i>Usa «Cambiar estado» en cada tarjeta. Los tickets cerrados no pueden modificarse. Cada columna muestra hasta 30 tickets recientes.</p>
<div class="sd-kanban-grid" aria-label="Tablero Kanban de tickets">
 @foreach(\App\Models\Ticket::STATUSES as $status)
  <section class="sd-kanban-column" aria-label="{{ \App\Models\Ticket::STATUS_LABELS[$status] }}">
   <div class="card shadow-sm h-100">
    <div class="card-header d-flex align-items-center justify-content-between gap-2">
     <h2 class="h6 fw-semibold mb-0">{{ \App\Models\Ticket::STATUS_LABELS[$status] }}</h2>
     <span class="badge text-bg-secondary ms-auto">{{ $counts[$status] }}</span>
    </div>
    <div class="card-body p-2 bg-body-tertiary">
    @forelse($columns[$status] as $ticket)
     <article class="card shadow-sm mb-2">
      <div class="card-body p-3">
       <div class="d-flex justify-content-between gap-2 align-items-start mb-2">
        <a href="{{ route('tickets.show',$ticket) }}" class="fw-bold text-decoration-none">{{ $ticket->code }}</a>
        <span class="badge {{ $ticket->priorityBadgeClass() }}">{{ $ticket->priorityLabel() }}</span>
       </div>
       <p class="mb-2 fw-semibold small" style="overflow-wrap:anywhere">{{ $ticket->title }}</p>
       <div class="small text-secondary mb-1"><i class="bi bi-building me-1"></i>{{ $ticket->department?->name ?? 'Sin departamento' }}</div>
       <div class="small text-secondary mb-2"><i class="bi bi-person me-1"></i>{{ $ticket->assignee?->name === 'Evaluador PlantCore' ? 'Evaluador demo' : ($ticket->assignee?->name ?? 'Sin asignar') }}</div>
       @if($ticket->due_at)
        <div class="small {{ $ticket->due_at->isPast() && !in_array($ticket->status,['resolved','closed']) ? 'text-danger fw-semibold' : 'text-secondary' }} mb-2">
          <i class="bi bi-clock me-1"></i>{{ $ticket->due_at->format('d/m/Y H:i') }}
        </div>
       @endif
       @if($status !== 'closed' && \App\Support\TicketAccess::handles(auth()->user(),$ticket->department_id))
        <form method="POST" action="{{ route('tickets.kanban.move',$ticket) }}">
         @csrf @method('PATCH')
         <label for="move-{{ $ticket->id }}" class="form-label small mb-1">Cambiar estado</label>
         <div class="d-flex gap-2">
          <select id="move-{{ $ticket->id }}" name="status" class="form-select form-select-sm" aria-label="Estado de {{ $ticket->code }}">
           @foreach(\App\Models\Ticket::STATUSES as $next)
            <option value="{{ $next }}" @selected($next===$ticket->status)>{{ \App\Models\Ticket::STATUS_LABELS[$next] }}</option>
           @endforeach
          </select>
          <button class="btn btn-sm btn-outline-primary" type="submit" title="Guardar estado de {{ $ticket->code }}"><i class="bi bi-check-lg"></i><span class="visually-hidden">Guardar</span></button>
         </div>
        </form>
       @endif
      </div>
     </article>
    @empty
     <div class="text-center text-secondary small py-4">Sin solicitudes en este estado</div>
    @endforelse
    @if($counts[$status] > count($columns[$status]))
     <p class="text-center small text-secondary mt-2">Mostrando 30 de {{ $counts[$status] }}. <a href="{{ route('tickets.index',['status'=>$status]) }}">Ver todas</a></p>
    @endif
    </div>
   </div>
  </section>
 @endforeach
</div>
@endsection
