@extends('layouts.app')
@section('heading','Nueva solicitud')
@section('content')
<div class="mb-4"><h2 class="h4 fw-semibold text-body mb-1">Registrar solicitud</h2><p class="text-secondary mb-0">Indica lo que necesitas. El sistema asignará un folio y calculará el plazo según el servicio.</p></div>
<div class="row g-3">
 <div class="col-12 col-xl-8">
  <div class="card card-primary card-outline shadow-sm">
   <div class="card-header"><h3 class="card-title fw-semibold">Datos de la solicitud</h3></div>
   <div class="card-body">
    <form method="POST" action="{{ route('tickets.store') }}" id="ticket-create-form">
     @csrf
     <div class="mb-3"><label for="title" class="form-label fw-semibold">Asunto</label><input class="form-control" id="title" name="title" maxlength="180" minlength="5" value="{{ old('title') }}" placeholder="Ej. Solicitud de acceso a servidor de pruebas" required></div>
     <div class="row g-3 mb-3">
      <div class="col-md-6"><label for="department" class="form-label fw-semibold">Departamento</label><select class="form-select" name="department_id" id="department" required><option value="">Seleccionar departamento</option>@foreach($departments as $dep)<option value="{{ $dep->id }}" @selected(old('department_id')==$dep->id)>{{ $dep->name }}</option>@endforeach</select></div>
      <div class="col-md-6"><label for="category" class="form-label fw-semibold">Servicio o categoría</label><select class="form-select" name="ticket_category_id" id="category" required><option value="">Selecciona primero un departamento</option>@foreach($categories as $cat)<option data-department="{{ $cat->department_id }}" value="{{ $cat->id }}" @selected(old('ticket_category_id')==$cat->id)>{{ $cat->name }} · {{ $cat->sla_hours }} h</option>@endforeach</select></div>
     </div>
     <div class="mb-3"><label for="priority" class="form-label fw-semibold">Prioridad</label><select name="priority" id="priority" class="form-select">@foreach(['low'=>'Baja','normal'=>'Normal','high'=>'Alta','critical'=>'Crítica'] as $value=>$label)<option value="{{ $value }}" @selected(old('priority','normal')===$value)>{{ $label }}</option>@endforeach</select></div>
     <div class="mb-4"><label for="description" class="form-label fw-semibold">Descripción</label><textarea class="form-control" name="description" id="description" rows="6" minlength="10" maxlength="10000" placeholder="Describe el problema, qué proceso afecta y el resultado esperado." required>{{ old('description') }}</textarea><div class="form-text">Evita agregar datos confidenciales en la demostración.</div></div>
     <div class="d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i>Crear ticket</button><a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">Cancelar</a></div>
    </form>
   </div>
  </div>
 </div>
 <div class="col-12 col-xl-4">
  <div class="card shadow-sm">
   <div class="card-header"><h3 class="card-title fw-semibold"><i class="bi bi-info-circle me-2"></i>Proceso de atención</h3></div>
   <div class="card-body">
    <ol class="ps-3 mb-3"><li class="mb-3">La solicitud recibe un folio único.</li><li class="mb-3">Se canaliza al departamento y servicio seleccionados.</li><li class="mb-3">Un agente puede responder, asignar responsables o transferirla.</li><li>Consulta el historial y estado desde la bandeja.</li></ol>
    <div class="alert alert-info mb-0"><i class="bi bi-clock me-1"></i>El plazo se calcula en horas corridas, no en horario laboral.</div>
   </div>
  </div>
 </div>
</div>
<script>
(function(){
const dep=document.getElementById('department'),cat=document.getElementById('category');
function sync(){
 for(const option of cat.options){
  if(!option.value)continue;
  const available=option.dataset.department===dep.value;
  option.hidden=!available;option.disabled=!available;
 }
 if(cat.selectedOptions[0]?.disabled)cat.value='';
}
dep.addEventListener('change',sync);sync();
})();
</script>
@endsection
