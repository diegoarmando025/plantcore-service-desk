<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso · PlantCore Service Desk</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte-overrides.css') }}">
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header text-center py-4">
            <a href="{{ route('login') }}" class="h3 text-decoration-none fw-semibold text-body">PlantCore <span class="fw-normal">Service Desk</span></a>
        </div>
        <div class="card-body login-card-body">
            <p class="login-box-msg">Accede al centro de solicitudes y atención interna</p>
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <label class="form-label" for="email">Correo corporativo</label>
                <div class="input-group mb-3">
                    <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                </div>
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-group mb-4">
                    <input id="password" class="form-control" type="password" name="password" autocomplete="current-password" required>
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                </div>
                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
            </form>
            <p class="text-secondary small text-center mt-4 mb-0">Acceso para usuarios autorizados.</p>
        </div>
    </div>
</div>
</body>
</html>
