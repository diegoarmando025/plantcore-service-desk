@extends('layouts.app')
@section('heading','Bandeja de solicitudes')
@section('content')
<div class="sd-pagehead">
 <div><p class="sd-overline mb-1">OPERACIÓN / SOLICITUDES</p><h2 class="sd-page-title">Bandeja de tickets</h2><p class="text-secondary mb-0">Consulta, filtra y atiende solicitudes en un solo lugar.</p></div>
 <a class="btn btn-primary" href="{{ route('tickets.create') }}"><i class="bi bi-plus-lg me-1"></i>Nuevo ticket</a>
</div>
<div class="sd-quickfilters mb-3">
 <a class="sd-filter-chip {{ empty($filters) || !array_filter($filters) ? 'active':'' }}" href="{{ route('tickets.index') }}">Todas</a>
 <a class="sd-filter-chip {{ ($filters['mine']??'')==='1'?'active':'' }}" href="{{ route('tickets.index',['mine'=>1]) }}">Creadas por mí</a>
 <a class="sd-filter-chip {{ ($filters['status']??'')==='open'?'active':'' }}" href="{{ route('tickets.index',['status'=>'open']) }}">Abiertas</a>
 <a class="sd-filter-chip {{ ($filters['status']??'')==='in_progress'?'active':'' }}" href="{{ route('tickets.index',['status'=>'in_progress']) }}">En proceso</a>
 <a class="sd-filter-chip {{ ($filters['priority']??'')==='critical'?'active':'' }}" href="{{ route('tickets.index',['priority'=>'critical']) }}">Críticas</a>
</div>
<section class="sd-surface mb-4">
 <form method="GET" action="{{ route('tickets.index') }}" class="sd-filter-form">
  <div><label class="form-label" for="q">Buscar</label><input class="form-control" id="q" name="q" maxlength="100" value="{{ $filters['q']??'' }}" placeholder="Folio o asunto"></div>
  <div><label class="form-label" for="department_id">Departamento</label><select class="form-select" id="department_id" name="department_id"><option value="">Todos</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(($filters['department_id']??'')==$d->id)>{{ $d->name }}</option>@endforeach</select></div>
  <div><label class="form-label" for="status">Estado</label><select class="form-select" id="status" name="status"><option value="">Todos</option>@foreach(\App\Models\Ticket::STATUSES as $s)<option value="{{ $s }}" @selected(($filters['status']??'')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
  <div><label class="form-label" for="priority">Prioridad</label><select class="form-select" id="priority" name="priority"><option value="">Todas</option>@foreach(\App\Models\Ticket::PRIORITIES as $s)<option value="{{ $s }}" @selected(($filters['priority']??'')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
  <div class="sd-filter-actions"><button class="btn btn-primary" type="submit">Aplicar</button><a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">Limpiar</a></div>
 </form>
</section>
<section class="sd-surface">
 <div class="sd-section-heading"><div><h3>Solicitudes</h3><p>{{ number_format($tickets->total()) }} resultados según los filtros seleccionados</p></div><span class="sd-small-meta">Página {{ $tickets->currentPage() }} de {{ $tickets->lastPage() }}</span></div>
 <div class="sd-responsive-table">
  <table class="sd-table sd-full-table">
   <thead><tr><th>Ticket</th><th>Departamento</th><th>Solicitante</th><th>Responsable</th><th>Prioridad</th><th>Estado</th><th class="text-end">Detalle</th></tr></thead>
   <tbody>
   @forelse($tickets as $t)
    <tr>
     <td><a href="{{ route('tickets.show',$t) }}" class="sd-ticket-link">{{ $t->code }}</a><div class="sd-cell-subtitle">{{ $t->title }}</div></td>
     <td>{{ $t->department?->name ?? 'Sin departamento' }}</td>
     <td>{{ $t->requester?->name ?? 'Usuario' }}</td>
     <td>{{ $t->assignee?->name ?? 'Sin asignar' }}</td>
     <td><span class="pill {{ $t->priority }}">{{ ucfirst($t->priority) }}</span></td>
     <td><span class="pill status">{{ ucfirst(str_replace('_',' ',$t->status)) }}</span></td>
     <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('tickets.show',$t) }}">Abrir <i class="bi bi-arrow-up-right ms-1"></i></a></td>
    </tr>
   @empty
    <tr><td colspan="7" class="sd-empty"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Sin resultados para estos filtros. <a href="{{ route('tickets.index') }}">Mostrar todas</a>.</td></tr>
   @endforelse
   </tbody>
  </table>
 </div>
 <div class="sd-pagination">{{ $tickets->links() }}</div>
</section>
@endsection
