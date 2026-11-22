@extends('layouts.app')
@section('title','Reportes operativos')
@section('content')
<div class="d-flex justify-content-between flex-wrap align-items-start gap-3 mb-3">
 <div><h1 class="h4 fw-bold mb-1">Reportes operativos</h1><p class="text-secondary mb-0">Actividad, tiempos de resolución y cumplimiento de SLA a partir de tickets reales del período.</p></div>
 <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary"><i class="bi bi-inboxes me-1"></i>Ver solicitudes</a>
</div>
<div class="card shadow-sm mb-3">
 <div class="card-header"><h2 class="card-title fw-semibold"><i class="bi bi-calendar-range me-1"></i>Periodo del reporte</h2></div>
 <div class="card-body">
  <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
   <div class="col-12 col-sm-4 col-lg-3"><label for="report_from" class="form-label">Desde</label><input class="form-control" type="date" id="report_from" name="from" value="{{ $from->toDateString() }}" required></div>
   <div class="col-12 col-sm-4 col-lg-3"><label for="report_to" class="form-label">Hasta</label><input class="form-control" type="date" id="report_to" name="to" value="{{ $to->toDateString() }}" required></div>
   <div class="col-12 col-sm-4 col-lg-6 d-flex flex-wrap gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Aplicar</button>
    <a href="{{ route('reports.export',['from'=>$from->toDateString(),'to'=>$to->toDateString()]) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Exportar CSV</a></div>
  </form>
 </div>
</div>
<div class="row g-3 mb-3">
 @foreach([
  ['Solicitudes creadas',$total,'primary'],
  ['Resueltas o cerradas',$completedCount,'success'],
  ['Fuera de plazo',$overdue,'danger'],
  ['SLA cumplidos',$slaMet.' / '.$slaEligible,'info'],
  ['Resolución promedio',$avgHours===null?'—':number_format($avgHours,1).' h','secondary']
 ] as [$label,$value,$color])
  <div class="col-6 col-lg-4 col-xxl">
   <div class="small-box text-bg-{{ $color }} mb-0 h-100"><div class="inner"><h3>{{ $value }}</h3><p>{{ $label }}</p></div></div>
  </div>
 @endforeach
</div>
<div class="alert alert-light border small" role="note"><i class="bi bi-info-circle me-1"></i>El periodo se filtra por fecha de creación. El SLA compara la hora de resolución con el vencimiento registrado. Los plazos están expresados en horas corridas, no laborales. Los tickets sin vencimiento no entran al indicador de SLA.</div>
<div class="row g-3 mb-4">
 @foreach([['Por estado',$statuses],['Por departamento',$departments],['Por agente asignado',$agents]] as [$heading,$rows])
  <div class="col-12 col-xl-4">
   <div class="card shadow-sm h-100">
    <div class="card-header"><h2 class="card-title fw-semibold">{{ $heading }}</h2></div>
    <div class="card-body p-0">
     <div class="table-responsive"><table class="table table-striped table-hover mb-0 align-middle"><thead><tr><th>Concepto</th><th class="text-end">Tickets</th></tr></thead><tbody>
     @forelse($rows as $row)
      <tr><td>{{ $row['name'] }}</td><td class="text-end fw-semibold">{{ $row['tickets'] }}</td></tr>
     @empty<tr><td colspan="2" class="text-center py-4 text-secondary">Sin registros en el periodo</td></tr>@endforelse
     </tbody></table></div>
    </div>
   </div>
  </div>
 @endforeach
</div>
@endsection
