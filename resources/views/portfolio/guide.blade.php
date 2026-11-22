@extends('layouts.app')
@section('title','Recorrido de evaluación')
@section('heading','Recorrido de evaluación')
@section('content')
<div class="card shadow-sm mb-4">
 <div class="card-body">
  <h2 class="h4">PlantCore Service Desk · Demo interactiva</h2>
  <p>Estás en el sistema real, conectado a MySQL. Puedes crear solicitudes, responder, asignar responsables y transferir tickets entre departamentos.</p>
  <p class="text-secondary small">Los visitantes comparten una cuenta y datos ficticios. Sus cambios permanecen visibles para otros visitantes hasta que se restablece el escenario. No introduzcas información personal o confidencial.</p>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="{{ route('tickets.create') }}">Crear solicitud</a>
    <a class="btn btn-outline-primary" href="{{ route('tickets.index') }}">Ver tickets demo</a>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Ver indicadores</a>
    <a class="btn btn-outline-primary" href="{{ route('tickets.kanban') }}">Tablero Kanban</a>
  </div>
 </div>
</div>
<div class="card"><div class="card-body">
 <h3 class="h5">Actividad disponible ({{ $total }} tickets)</h3>
 <div class="list-group list-group-flush">
 @forelse($recent as $ticket)
  <a class="list-group-item list-group-item-action" href="{{ route('tickets.show', $ticket) }}">
   <strong>{{ $ticket->code }}</strong> — {{ $ticket->title }} <span class="text-secondary">({{ $ticket->statusLabel() }})</span>
  </a>
 @empty
  <p>No hay tickets de ejemplo. Puedes crear el primero.</p>
 @endforelse
 </div>
</div></div>
@endsection
