@extends('layouts.app')
@section('title', 'Administración')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><h1 class="h4 fw-bold mb-1">Administración</h1><p class="text-secondary mb-0">Departamentos, equipos, servicios y accesos del personal.</p></div>
</div>
<div class="row g-3">
  <div class="col-12 col-xl-6">
    <section class="card shadow-sm h-100"><div class="card-header"><h2 class="card-title fw-semibold">Nuevo departamento</h2></div><div class="card-body">
      <form method="POST" action="{{ route('admin.departments.store') }}">@csrf
        <div class="mb-3"><label for="dept_name" class="form-label">Nombre</label><input class="form-control" id="dept_name" name="name" required maxlength="120" placeholder="Ej. Recursos Humanos"></div>
        <div class="mb-3"><label for="dept_code" class="form-label">Código</label><input class="form-control" id="dept_code" name="code" required maxlength="30" placeholder="RH"></div>
        <button type="submit" class="btn btn-primary">Crear departamento</button>
      </form>
    </div></section>
  </div>
  <div class="col-12 col-xl-6">
    <section class="card shadow-sm h-100"><div class="card-header"><h2 class="card-title fw-semibold">Nuevo equipo</h2></div><div class="card-body">
      <form method="POST" action="{{ route('admin.teams.store') }}">@csrf
        <div class="mb-3"><label for="team_department" class="form-label">Departamento</label><select class="form-select" id="team_department" name="department_id" required>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div class="mb-3"><label for="team_name" class="form-label">Nombre</label><input class="form-control" id="team_name" name="name" required maxlength="120" placeholder="Infraestructura"></div>
        <div class="mb-3"><label for="team_code" class="form-label">Código</label><input class="form-control" id="team_code" name="code" required maxlength="30" placeholder="INFRA"></div>
        <button type="submit" class="btn btn-primary">Crear equipo</button>
      </form>
    </div></section>
  </div>
  <div class="col-12 col-xl-6">
    <section class="card shadow-sm h-100"><div class="card-header"><h2 class="card-title fw-semibold">Nuevo servicio</h2></div><div class="card-body">
      <form method="POST" action="{{ route('admin.categories.store') }}">@csrf
        <div class="mb-3"><label for="cat_department" class="form-label">Departamento</label><select class="form-select" id="cat_department" name="department_id" required>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div class="mb-3"><label for="cat_team" class="form-label">Equipo predeterminado</label><select class="form-select" id="cat_team" name="team_id"><option value="">Sin equipo</option>@foreach($departments as $d)@foreach($d->teams as $t)<option value="{{ $t->id }}" data-department="{{ $d->id }}">{{ $d->name }} · {{ $t->name }}</option>@endforeach @endforeach</select></div>
        <div class="mb-3"><label for="cat_name" class="form-label">Nombre</label><input class="form-control" id="cat_name" name="name" maxlength="120" required></div>
        <div class="mb-3"><label for="cat_code" class="form-label">Código</label><input class="form-control" id="cat_code" name="code" maxlength="30" required></div>
        <div class="mb-3"><label for="cat_sla" class="form-label">Plazo SLA (horas transcurridas)</label><input class="form-control" id="cat_sla" name="sla_hours" type="number" min="1" max="720" value="48" required></div>
        <button type="submit" class="btn btn-primary">Crear servicio</button>
      </form>
    </div></section>
  </div>
  <div class="col-12 col-xl-6">
    <section class="card shadow-sm h-100"><div class="card-header"><h2 class="card-title fw-semibold">Nuevo usuario</h2></div><div class="card-body">
      <form method="POST" action="{{ route('admin.users.store') }}">@csrf
        <div class="mb-3"><label for="usr_name" class="form-label">Nombre</label><input class="form-control" id="usr_name" name="name" maxlength="120" required></div>
        <div class="mb-3"><label for="usr_email" class="form-label">Correo</label><input class="form-control" id="usr_email" type="email" name="email" required></div>
        <div class="mb-3"><label for="usr_password" class="form-label">Contraseña inicial (mínimo 16 caracteres)</label><input class="form-control" id="usr_password" type="password" name="password" minlength="16" required autocomplete="new-password"></div>
        <div class="mb-3"><label for="usr_role" class="form-label">Rol</label><select class="form-select" id="usr_role" name="role"><option value="requester">Solicitante</option><option value="agent">Agente</option></select></div>
        <button type="submit" class="btn btn-primary">Crear usuario</button>
      </form>
    </div></section>
  </div>
  <div class="col-12 col-xl-6">
    <section class="card shadow-sm h-100"><div class="card-header"><h2 class="card-title fw-semibold">Acceso a departamento</h2></div><div class="card-body">
      <form method="POST" action="{{ route('admin.memberships.store') }}">@csrf
        <div class="mb-3"><label for="member_user" class="form-label">Agente</label><select class="form-select" id="member_user" name="user_id" required>@foreach($users->whereIn('role',['agent','admin']) as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
        <div class="mb-3"><label for="member_department" class="form-label">Departamento</label><select class="form-select" id="member_department" name="department_id" required>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div class="mb-3"><label for="member_role" class="form-label">Función</label><select class="form-select" id="member_role" name="role"><option value="agent">Agente</option><option value="lead">Líder</option></select></div>
        <button type="submit" class="btn btn-primary">Asignar acceso</button>
      </form>
    </div></section>
  </div>
  <div class="col-12 col-xl-6">
    <section class="card shadow-sm h-100"><div class="card-header"><h2 class="card-title fw-semibold">Acceso a equipo</h2></div><div class="card-body">
      <form method="POST" action="{{ route('admin.team-members.store') }}">@csrf
        <div class="mb-3"><label for="team_member" class="form-label">Agente</label><select class="form-select" id="team_member" name="user_id" required>@foreach($users->whereIn('role',['agent','admin']) as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
        <div class="mb-3"><label for="team_member_team" class="form-label">Equipo</label><select class="form-select" id="team_member_team" name="team_id" required>@foreach($departments as $d)@foreach($d->teams as $t)<option value="{{ $t->id }}">{{ $d->name }} · {{ $t->name }}</option>@endforeach @endforeach</select></div>
        <button type="submit" class="btn btn-primary">Asignar al equipo</button>
      </form>
    </div></section>
  </div>
</div>
<section class="card shadow-sm mt-3"><div class="card-header"><h2 class="card-title fw-semibold">Catálogo de atención</h2></div>
 <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Departamento</th><th>Equipos</th><th>Servicios</th></tr></thead><tbody>
 @forelse($departments as $d)<tr><td><strong>{{ $d->name }}</strong><div class="small text-secondary">{{ $d->code }}</div></td><td>{{ $d->teams->pluck('name')->join(', ') ?: 'Sin equipos' }}</td><td>{{ $d->categories->pluck('name')->join(', ') ?: 'Sin servicios' }}</td></tr>
 @empty<tr><td colspan="3" class="text-center text-secondary py-4">Sin departamentos configurados.</td></tr>@endforelse
 </tbody></table></div>
</section>
<script>
(function () {
 const department = document.getElementById('cat_department'), team = document.getElementById('cat_team');
 if (!department || !team) return;
 function filterTeams() {
  for (const option of team.options) {
   if (!option.value) continue;
   option.hidden = option.dataset.department !== department.value;
   option.disabled = option.hidden;
  }
  if (team.selectedOptions[0]?.disabled) team.value = '';
 }
 department.addEventListener('change', filterTeams);
 filterTeams();
})();
</script>
@endsection
