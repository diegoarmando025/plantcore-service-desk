@extends('layouts.app')
@section('heading','Bandeja de solicitudes')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
 <div><h2 class="h4 fw-semibold text-body mb-1">Bandeja de tickets</h2><p class="text-secondary mb-0">Busca, filtra y atiende las solicitudes de tu área.</p></div>
 <a class="btn btn-primary" href="{{ route('tickets.create') }}"><i class="bi bi-plus-lg me-1"></i>Nuevo ticket</a>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
 @foreach([
 ['label'=>'Todas','params'=>[]],['label'=>'Creadas por mí','params'=>['mine'=>1]],
 ['label'=>'Abiertas','params'=>['status'=>'open']],['label'=>'En proceso','params'=>['status'=>'in_progress']],
 ['label'=>'Críticas','params'=>['priority'=>'critical']]
 ] as $shortcut)
  <a class="btn btn-sm {{ request()->query()==$shortcut['params'] ? 'btn-primary':'btn-outline-secondary' }}" href="{{ route('tickets.index',$shortcut['params']) }}">{{ $shortcut['label'] }}</a>
 @endforeach
</div>
<div class="card shadow-sm mb-3">
 <div class="card-header"><h3 class="card-title">Filtros</h3></div>
 <div class="card-body">
 <form method="GET" action="{{ route('tickets.index') }}" class="row g-3 align-items-end">
  <div class="col-12 col-md-4"><label class="form-label" for="q">Folio o asunto</label><input class="form-control" id="q" name="q" maxlength="100" value="{{ $filters['q']??'' }}" placeholder="Buscar solicitud"></div>
  <div class="col-12 col-md-3"><label class="form-label" for="department_id">Departamento</label><select class="form-select" id="department_id" name="department_id"><option value="">Todos</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(($filters['department_id']??'')==$d->id)>{{ $d->name }}</option>@endforeach</select></div>
  <div class="col-6 col-md-2"><label class="form-label" for="status">Estado</label><select class="form-select" id="status" name="status"><option value="">Todos</option>@foreach(\App\Models\Ticket::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status']??'')===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
  <div class="col-6 col-md-2"><label class="form-label" for="priority">Prioridad</label><select class="form-select" id="priority" name="priority"><option value="">Todas</option>@foreach(\App\Models\Ticket::PRIORITIES as $priority)<option value="{{ $priority }}" @selected(($filters['priority']??'')===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
  <div class="col-12 col-md-1 d-flex gap-2"><button type="submit" class="btn btn-primary" title="Filtrar"><i class="bi bi-funnel"></i><span class="visually-hidden">Aplicar filtros</span></button><a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i><span class="visually-hidden">Limpiar</span></a></div>
 </form>
 </div>
</div>
<div class="card shadow-sm">
 <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">{{ $tickets->total() }} solicitudes</h3><span class="small text-secondary ms-auto">Página {{ $tickets->currentPage() }} / {{ $tickets->lastPage() }}</span></div>
 <div class="card-body p-0 table-responsive">
  <table class="table table-hover align-middle mb-0">
   <thead><tr><th>Ticket</th><th>Departamento</th><th>Solicitante</th><th>Responsable</th><th>Prioridad</th><th>Estado</th><th></th></tr></thead>
   <tbody>@forelse($tickets as $t)
    <tr>
     <td><a class="fw-bold text-decoration-none" href="{{ route('tickets.show',$t) }}">{{ $t->code }}</a><div class="small text-secondary">{{ $t->title }}</div></td>
     <td>{{ $t->department?->name ?? '—' }}</td><td>{{ $t->requester?->name ?? '—' }}</td><td>{{ $t->assignee?->name ?? 'Sin asignar' }}</td>
     <td><span class="badge {{ $t->priority==='critical'?'text-bg-danger':($t->priority==='high'?'text-bg-warning':'text-bg-secondary') }}">{{ ucfirst($t->priority) }}</span></td>
     <td><span class="badge text-bg-light border">{{ ucfirst(str_replace('_',' ',$t->status)) }}</span></td>
     <td><a class="btn btn-sm btn-outline-primary" href="{{ route('tickets.show',$t) }}">Abrir</a></td>
    </tr>
   @empty<tr><td colspan="7" class="text-center text-secondary py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No hay solicitudes para los filtros seleccionados.</td></tr>@endforelse</tbody>
  </table>
 </div>
 <div class="card-footer">{{ $tickets->links() }}</div>
</div>
@endsection
