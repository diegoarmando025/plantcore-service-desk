@extends('layouts.app')
@section('heading','Centro de operación')
@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
 <div><p class="text-secondary small text-uppercase fw-semibold mb-1">Mesa de servicios / Operación</p>
 <h2 class="h4 text-body fw-bold mb-1">Resumen de solicitudes</h2>
 <p class="text-secondary mb-0">Indicadores y actividad calculados sobre tus tickets visibles.</p></div>
 <a href="{{ route('tickets.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva solicitud</a>
</div>
<div class="row g-3 mb-4">
 @foreach([
  ['label'=>'Solicitudes','key'=>'total','icon'=>'bi-inboxes','tone'=>'primary'],
  ['label'=>'En atención','key'=>'open','icon'=>'bi-arrow-repeat','tone'=>'info'],
  ['label'=>'Fuera de plazo','key'=>'overdue','icon'=>'bi-exclamation-triangle','tone'=>'danger'],
  ['label'=>'Por vencer (24 h)','key'=>'due_soon','icon'=>'bi-clock-history','tone'=>'warning'],
  ['label'=>'Resueltas','key'=>'resolved','icon'=>'bi-check-circle','tone'=>'success']
 ] as $metric)
 <div class="col-6 col-lg-4 col-xxl">
  <div class="small-box text-bg-{{ $metric['tone'] }} h-100 mb-0">
    <div class="inner"><h3>{{ number_format($counts[$metric['key']]) }}</h3><p>{{ $metric['label'] }}</p></div>
    <i class="small-box-icon bi {{ $metric['icon'] }}" aria-hidden="true"></i>
  </div>
 </div>
 @endforeach
</div>
<div class="row g-3 mb-4">
 <div class="col-12 col-xl-7">
  <div class="card h-100 shadow-sm">
   <div class="card-header"><h3 class="card-title fw-semibold">Distribución por estado</h3><div class="card-tools"><a class="btn btn-sm btn-outline-secondary" href="{{ route('tickets.index') }}">Ver bandeja</a></div></div>
   <div class="card-body">
   @foreach(['open'=>'Abiertas','assigned'=>'Asignadas','in_progress'=>'En proceso','waiting'=>'En espera','resolved'=>'Resueltas','closed'=>'Cerradas'] as $status=>$label)
    @php($n=(int)($statusCounts[$status]??0))
    <div class="row align-items-center g-2 mb-3">
     <div class="col-4 col-md-3 text-secondary small">{{ $label }}</div>
     <div class="col"><div class="progress" role="progressbar" aria-label="{{ $label }}" aria-valuenow="{{ $n }}" aria-valuemin="0" aria-valuemax="{{ max(1,$counts['total']) }}" style="height:12px">
      <div class="progress-bar bg-{{ in_array($status,['resolved','closed']) ? 'success' : ($status==='waiting' ? 'warning' : 'primary') }}" style="width:{{ $counts['total'] ? round(100*$n/$counts['total']) : 0 }}%"></div>
     </div></div>
     <div class="col-auto fw-semibold">{{ $n }}</div>
    </div>
   @endforeach
   </div>
  </div>
 </div>
 <div class="col-12 col-xl-5">
  <div class="card h-100 shadow-sm">
   <div class="card-header"><h3 class="card-title fw-semibold">Carga por agente</h3><div class="card-tools"><span class="badge text-bg-secondary">{{ $counts['unassigned'] }} sin asignar</span></div></div>
   <div class="card-body p-0">
    <ul class="list-group list-group-flush">
    @forelse($workload as $agent)
      <li class="list-group-item d-flex justify-content-between align-items-center"><span><i class="bi bi-person-circle text-secondary me-2"></i>{{ $agent['name'] === 'Evaluador PlantCore' ? 'Evaluador demo' : $agent['name'] }}</span><span class="badge text-bg-primary rounded-pill">{{ $agent['total'] }}</span></li>
    @empty
      <li class="list-group-item text-secondary py-4 text-center">No hay tickets activos asignados.</li>
    @endforelse
    </ul>
   </div>
  </div>
 </div>
</div>
<div class="row g-3">
 <div class="col-12 col-xl-6">
  <div class="card h-100 shadow-sm">
   <div class="card-header"><h3 class="card-title fw-semibold">Próximos vencimientos</h3></div>
   <div class="card-body p-0 table-responsive">
    <table class="table table-hover align-middle mb-0"><thead><tr><th>Solicitud</th><th>Prioridad</th><th>Plazo</th></tr></thead>
    <tbody>@forelse($urgent as $ticket)
     <tr><td><a class="fw-semibold" href="{{ route('tickets.show',$ticket) }}">{{ $ticket->code }}</a><div class="small text-secondary">{{ $ticket->title }}</div></td>
      <td><span class="badge {{ $ticket->priorityBadgeClass() }}">{{ $ticket->priorityLabel() }}</span></td>
      <td class="{{ $ticket->due_at->isPast() ? 'text-danger fw-semibold' : 'text-body-secondary' }} small">{{ $ticket->due_at->format('d/m/Y H:i') }}</td>
     </tr>
    @empty<tr><td colspan="3" class="text-center text-secondary py-4">No hay solicitudes pendientes de vencer.</td></tr>@endforelse</tbody></table>
   </div>
  </div>
 </div>
 <div class="col-12 col-xl-6">
  <div class="card h-100 shadow-sm">
   <div class="card-header"><h3 class="card-title fw-semibold">Últimos tickets</h3><div class="card-tools"><a class="btn btn-sm btn-outline-secondary" href="{{ route('tickets.index') }}">Ver todos</a></div></div>
   <div class="card-body p-0">
    <div class="list-group list-group-flush">
    @forelse($recent as $ticket)
     <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2" href="{{ route('tickets.show',$ticket) }}">
      <div class="overflow-hidden"><strong class="small">{{ $ticket->code }}</strong><div class="text-truncate">{{ $ticket->title }}</div><small class="text-secondary">{{ $ticket->department?->name }}</small></div><span class="badge {{ $ticket->statusBadgeClass() }}">{{ $ticket->statusLabel() }}</span>
     </a>
    @empty<div class="p-4 text-center text-secondary">No hay solicitudes recientes.</div>@endforelse
    </div>
   </div>
  </div>
 </div>
</div>
@endsection
