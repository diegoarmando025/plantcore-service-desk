# PlantCore Service Desk

Laravel 13 con MySQL y módulos de tickets multiddepartamentales (TI, RH, Finanzas,
Compras y Mantenimiento): departamentos, equipos, categorías, solicitudes, asignaciones,
transferencias entre áreas, comentarios, notas internas, historial y prioridades.

## Estructura
- `app` reglas, controladores y modelos
- `database/migrations` esquema MySQL
- `database/seeders` datos de prueba SOLO para `local`/`testing`
- `tests/Feature` pruebas de operaciones y permisos
- `deploy/PUBLICACION.md` despliegue GitHub + Hostinger

## Local MySQL
Copiar `.env.example` a `.env` y ajustar las credenciales de MySQL.
Crear bases `plantcore_service_desk` y `plantcore_service_desk_test` (solo local).
Ejecutar `composer install`, `php artisan key:generate`, `php artisan migrate --seed`,
`php artisan test` y `php artisan serve`.
Los tests usan la base _test; no ejecutar con producción.

## Producción
No ejecutar el seeder demo. Usar `php artisan service-desk:create-admin correo@dominio`.
Seguir `deploy/PUBLICACION.md`.

## Alcance
Aplicación empresarial inicial, NO SaaS multitenant comercial. Todavía no incluye
subtickets, flujos de aprobación, correo, adjuntos, facturación ni SLA laboral.

## Inicialización de Hostinger
Producción: `php artisan migrate --force`, `php artisan service-desk:install-catalog --force`,
y `php artisan service-desk:create-admin correo@tudominio.com`.
No ejecutes `db:seed` en producción: la demo se reserva para local/testing.
El subdominio es `https://service-desk.devalejandre.com`.
