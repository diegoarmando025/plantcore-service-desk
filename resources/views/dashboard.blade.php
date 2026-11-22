@extends('layouts.app')
@section('heading','Centro de operación')
@section('content')
<div class="sd-pagehead">
  <div>
    <p class="sd-overline mb-1">PLANTCORE / MESA DE SERVICIOS</p>
    <h2 class="sd-page-title">Centro de operación</h2>
    <p class="text-secondary mb-0">Seguimiento de solicitudes y carga de atención en tiempo real.</p>
  </div>
  <a class="btn btn-primary" href="{{ route('tickets.create') }}"><i class="bi bi-plus-lg me-1"></i>Nueva solicitud</a>
</div>

<div class="sd-metrics mb-4">
 @foreach([
  ['label'=>'Solicitudes visibles','key'=>'total','icon'=>'bi-inboxes','tone'=>'blue','help'=>'Total dentro de tu alcance'],
  ['label'=>'En atención','key'=>'open','icon'=>'bi-arrow-repeat','tone'=>'indigo','help'=>'Abiertas, asignadas y en proceso'],
  ['label'=>'Fuera de plazo','key'=>'overdue','icon'=>'bi-exclamation-octagon','tone'=>'red','help'=>'Pendientes con SLA vencido'],
  ['label'=>'Por vencer','key'=>'due_soon','icon'=>'bi-clock-history','tone'=>'amber','help'=>'Próximas 24 horas'],
  ['label'=>'Resueltas','key'=>'resolved','icon'=>'bi-check2-circle','tone'=>'green','help'=>'Resueltas y cerradas']
 ] as $metric)
 <div class="sd-metric sd-tone-{{ $metric['tone'] }}">
   <div class="sd-metric-top"><span>{{ $metric['label'] }}</span><i class="bi {{ $metric['icon'] }}"></i></div>
   <strong>{{ number_format($counts[$metric['key']]) }}</strong>
   <small>{{ $metric['help'] }}</small>
 </div>
 @endforeach
</div>

<div class="sd-overview-grid mb-4">
 <section class="sd-surface">
   <div class="sd-section-heading"><div><h3>Distribución por estado</h3><p>Conteo real de solicitudes visibles</p></div><a href="{{ route('tickets.index') }}">Ver bandeja</a></div>
   @php($statusLabels=['open'=>'Abiertas','assigned'=>'Asignadas','in_progress'=>'En proceso','waiting'=>'En espera','resolved'=>'Resueltas','closed'=>'Cerradas'])
   <div class="sd-status-list">
   @foreach($statusLabels as $status => $label)
     @php($value=(int)($statusCounts[$status]??0))
     <div class="sd-status-item">
       <span>{{ $label }}</span>
       <div class="sd-track" role="img" aria-label="{{ $label }}: {{ $value }} solicitudes"><div class="sd-track-fill sd-status-{{ $status }}" style="width:{{ $counts['total']>0?round($value/$counts['total']*100):0 }}%"></div></div>
       <strong>{{ $value }}</strong>
     </div>
   @endforeach
   </div>
 </section>
 <section class="sd-surface">
   <div class="sd-section-heading"><div><h3>Carga por agente</h3><p>Solicitudes activas asignadas</p></div><span class="sd-small-meta">{{ $counts['unassigned'] }} sin asignar</span></div>
   @forelse($workload as $agent)
    <div class="sd-agent-row">
      <span class="sd-agent-avatar">{{ mb_strtoupper(mb_substr($agent['name'],0,1)) }}</span>
      <span class="sd-agent-name">{{ $agent['name'] }}</span>
      <strong>{{ $agent['total'] }}</strong>
    </div>
   @empty
    <p class="text-secondary mb-0">Todavía no hay tickets activos asignados.</p>
   @endforelse
   <a class="sd-inline-link" href="{{ route('tickets.index',['status'=>'open']) }}">Consultar solicitudes abiertas <i class="bi bi-arrow-right"></i></a>
 </section>
</div>

<div class="sd-overview-grid sd-overview-grid-bottom">
 <section class="sd-surface">
  <div class="sd-section-heading"><div><h3>Atención prioritaria</h3><p>Ordenadas por fecha de vencimiento</p></div><a href="{{ route('tickets.index') }}">Ver todas</a></div>
  <div class="sd-responsive-table">
  <table class="sd-table"><thead><tr><th>Ticket</th><th>Prioridad</th><th>Vencimiento</th></tr></thead>
  <tbody>
  @forelse($urgent as $ticket)
   <tr>
    <td><a class="sd-ticket-link" href="{{ route('tickets.show',$ticket) }}">{{ $ticket->code }}</a><div class="sd-cell-subtitle">{{ $ticket->title }}</div></td>
    <td><span class="pill {{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></td>
    <td><span class="{{ $ticket->due_at->isPast()?'sd-overdue':'sd-deadline' }}">{{ $ticket->due_at->format('d/m/Y H:i') }}</span></td>
   </tr>
  @empty
   <tr><td colspan="3" class="text-secondary">No hay vencimientos pendientes.</td></tr>
  @endforelse
  </tbody></table></div>
 </section>
 <section class="sd-surface">
  <div class="sd-section-heading"><div><h3>Actividad reciente</h3><p>Últimas solicitudes registradas</p></div><a href="{{ route('tickets.index') }}">Ver todas</a></div>
  <div class="sd-recent-list">
  @forelse($recent as $ticket)
   <a class="sd-recent-item" href="{{ route('tickets.show',$ticket) }}">
    <div><strong>{{ $ticket->code }}</strong><span>{{ $ticket->title }}</span><small>{{ $ticket->department?->name ?? 'Departamento' }}</small></div>
    <span class="pill status">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span>
   </a>
  @empty
   <p class="text-secondary">Aún no se registran solicitudes.</p>
  @endforelse
  </div>
 </section>
</div>
@endsection
