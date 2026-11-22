<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Desk · DevAlejandre</title>
    <link rel="icon" href="https://devalejandre.com/assets/brand/favicon.ico" sizes="any">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte-overrides.css') }}?v=da-brand-20261122">
</head>
<body class="bg-body-tertiary da-login-page" data-bs-theme="light">
<main class="da-login-shell">
    <section class="da-login-story" aria-label="Descripción de Service Desk">
        <div class="da-story-brand">
            <a href="https://devalejandre.com" class="da-logo-plate" aria-label="DevAlejandre">
                <img src="https://devalejandre.com/assets/brand/DevAlejandre_logo.svg" alt="DevAlejandre" width="194" height="36">
            </a>
            <span class="da-product-label">Service Desk</span>
        </div>
        <div>
            <div class="da-login-eyebrow">GESTIÓN DE SERVICIOS INTERNOS</div>
            <h1>Atiende cada solicitud. Da seguimiento hasta resolverla.</h1>
            <p>Gestiona tickets, prioridades, responsables y tiempos de atención desde una misma aplicación.</p>
            <div class="da-login-features">
                <span><i class="bi bi-check-circle me-2"></i> Tickets y asignaciones</span>
                <span><i class="bi bi-check-circle me-2"></i> Prioridades y SLA</span>
                <span><i class="bi bi-check-circle me-2"></i> Tablero y reportes</span>
            </div>
        </div>
        <div class="da-login-footer">Demostración técnica · Datos ficticios</div>
    </section>
    <section class="da-login-panel" aria-label="Acceso al sistema">
        <div class="da-login-form">
            <div class="da-login-eyebrow">ACCESO AL SISTEMA</div>
            <h2 class="h3 fw-semibold mb-2">Explora Service Desk</h2>
            <p class="text-secondary mb-4">Accede al escenario interactivo y recorre sus funciones sin solicitar credenciales.</p>
            @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('portfolio.enter') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-arrow-right-circle me-2"></i> Entrar al demo interactivo</button>
            </form>
            <p class="small text-secondary mt-2 mb-4">Entorno compartido. No introduzcas información personal ni de clientes.</p>
            <div class="da-login-divider"><span>Acceso privado</span></div>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3"><label for="email" class="form-label">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="username" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-4"><label for="password" class="form-label">Contraseña</label><input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <button type="submit" class="btn btn-outline-primary w-100">Iniciar sesión</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
