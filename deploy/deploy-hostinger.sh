#!/usr/bin/env bash
# Run ONLY on the dedicated Service Desk installation directory after configuring .env and backups.
set -euo pipefail
cd "$(dirname "$0")/.."
test -f artisan || { echo 'No se encontró artisan'; exit 1; }
test -f .env || { echo 'ERROR: falta .env privado; no se despliega'; exit 1; }
grep -Eq '^APP_ENV=production$' .env || { echo 'ERROR: APP_ENV debe ser production'; exit 1; }
grep -Eq '^DB_CONNECTION=mysql$' .env || { echo 'ERROR: DB_CONNECTION debe ser mysql'; exit 1; }
command -v composer >/dev/null || { echo 'Instala/configura Composer en SSH primero'; exit 1; }
php artisan down --retry=60 || true
trap 'php artisan up || true' EXIT
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan config:clear
php artisan migrate --force
php artisan service-desk:install-catalog --force
php artisan view:cache
php artisan config:cache
php artisan up
trap - EXIT
echo 'Código y migraciones desplegados. Comprueba HTTPS, login y permisos desde navegador.'
