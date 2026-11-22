@extends('layouts.app')
@section('heading','Nueva solicitud')
@section('content')<div class="pageintro"><div><h2>¿En qué podemos ayudarte?</h2><p class="muted">Selecciona el departamento y el tipo de servicio. Tu ticket llegará a su bandeja.</p></div></div>
<div class="twocol"><section class="panel pad"><form method="POST" action="{{ route('tickets.store') }}">@csrf
<label>Asunto<input type="text" name="title" maxlength="180" minlength="5" value="{{ old('title') }}" placeholder="Ej. Solicitud de acceso a servidor de pruebas" required></label>
<label>Departamento<select name="department_id" id="department" required><option value="">Seleccionar...</option>@foreach($departments as $dep)<option value="{{ $dep->id }}" @selected(old('department_id')==$dep->id)>{{ $dep->name }}</option>@endforeach</select></label>
<label>Servicio o categoría<select name="ticket_category_id" id="category" required><option value="">Selecciona primero un departamento</option>@foreach($categories as $cat)<option data-department="{{ $cat->department_id }}" value="{{ $cat->id }}" @selected(old('ticket_category_id')==$cat->id)>{{ $cat->name }} · {{ $cat->sla_hours }} h</option>@endforeach</select></label>
<label>Prioridad<select name="priority"><option value="normal">Normal</option><option value="low">Baja</option><option value="high">Alta</option><option value="critical">Crítica</option></select></label>
<label>Descripción<textarea name="description" rows="7" minlength="10" maxlength="10000" placeholder="Explica qué necesitas, a quién afecta y cuándo se requiere..." required>{{ old('description') }}</textarea></label>
<button class="btn btn-primary">Crear ticket</button> <a class="btn btn-secondary" href="{{ route('tickets.index') }}">Cancelar</a>
</form></section><aside class="panel pad tip"><h3>¿Cómo funciona?</h3><p>Tu solicitud se identifica con un folio único y se canaliza al departamento correspondiente.</p><p>Los agentes del área pueden asignarla a un equipo, responder, cambiar su estado y registrar notas internas.</p><p class="muted">El plazo mostrado en las categorías se mide en horas transcurridas, no en horario laboral.</p></aside></div>
<script>const d=document.getElementById('department'),c=document.getElementById('category');function filterCategories(){for(const o of c.options){if(!o.value)continue;o.hidden=o.dataset.department!==d.value;o.disabled=o.hidden}if(c.selectedOptions[0]?.disabled)c.value=''}d.addEventListener('change',filterCategories);filterCategories();</script>
@endsection
