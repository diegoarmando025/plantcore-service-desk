<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Service Desk') · PlantCore</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/adminlte-overrides.css') }}">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body border-bottom">
        <div class="container-fluid">
            <button class="nav-link btn" type="button" data-lte-toggle="sidebar" aria-label="Alternar menú"><i class="bi bi-list fs-5"></i></button>
            <span class="ms-3 text-secondary small d-none d-sm-inline">Centro de servicios corporativos</span>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-secondary small d-none d-md-inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-right me-1"></i> Salir</button>
                </form>
            </div>
        </div>
    </nav>
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="brand-link text-decoration-none">
                <span class="brand-text fw-semibold">PlantCore <span class="fw-normal">Service Desk</span></span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-3">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" aria-label="Navegación principal">
                    <li class="nav-header">OPERACIÓN</li>
                    <li class="nav-item"><a href="{{ route('portfolio.guide') }}" class="nav-link {{ request()->routeIs('portfolio.guide') ? 'active' : '' }}"><i class="nav-icon bi bi-map"></i><p>Guía del sistema</p></a></li>
                    <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="nav-icon bi bi-grid-1x2"></i><p>Panel general</p></a></li>
                    <li class="nav-item"><a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.index', 'tickets.show') ? 'active' : '' }}"><i class="nav-icon bi bi-inboxes"></i><p>Solicitudes</p></a></li>
                    <li class="nav-item"><a href="{{ route('tickets.create') }}" class="nav-link {{ request()->routeIs('tickets.create') ? 'active' : '' }}"><i class="nav-icon bi bi-plus-circle"></i><p>Nueva solicitud</p></a></li>
                    @if(auth()->user()->isAdmin())
                        <li class="nav-header">CONFIGURACIÓN</li>
                        <li class="nav-item"><a href="{{ route('admin.index') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}"><i class="nav-icon bi bi-gear"></i><p>Administración</p></a></li>
                    @endif
                </ul>
            </nav>
        </div>
    </aside>
    <main class="app-main">
        <div class="app-content-header"><div class="container-fluid">
            <h1 class="h4 mb-0">@yield('heading', 'Panel general')</h1>
        </div></div>
        <div class="app-content"><div class="container-fluid service-desk-content">
            @if(auth()->user()->is_portfolio_demo)
              <div class="alert alert-info py-2">Modo evaluación: datos ficticios compartidos entre visitantes. Evita información personal o confidencial. <a href="{{ route('portfolio.guide') }}">Ver recorrido</a></div>
            @endif
            @if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger"><strong>Revisa los datos:</strong><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </div></div>
    </main>
</div>
<script src="{{ asset('assets/vendor/bootstrap.bundle.min.js') }}" defer></script>
<script src="{{ asset('assets/vendor/adminlte.min.js') }}" defer></script>
</body>
</html>
