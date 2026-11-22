#!/usr/bin/env bash
# Guarded Service Desk deployment. Requires explicit target database and confirmation.
set -euo pipefail
cd "$(dirname "$0")/.."

PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
COMPOSER_BIN="${COMPOSER_BIN:-$(command -v composer || true)}"
EXPECTED_DB_DATABASE="${EXPECTED_DB_DATABASE:-}"

test -f artisan || { echo "ERROR: Laravel artisan not found"; exit 1; }
test -f .env || { echo "ERROR: private .env is missing"; exit 1; }
test -x "$PHP_BIN" || { echo "ERROR: PHP_BIN is not executable"; exit 1; }
test -n "$COMPOSER_BIN" && test -f "$COMPOSER_BIN" || { echo "ERROR: Composer executable not found"; exit 1; }
test -n "$EXPECTED_DB_DATABASE" || { echo "ERROR: set EXPECTED_DB_DATABASE to the dedicated Service Desk MySQL database name"; exit 1; }

# Bootstrap Laravel using the same PHP interpreter without displaying secrets.
runtime="$("$PHP_BIN" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
$driver = config("database.default");
$name = config("database.connections." . $driver . ".database");
$environment = config("app.env");
if ($driver !== "mysql" || $environment !== "production") {
    fwrite(STDERR, "ERROR: expected production with mysql, found " . $environment . "/" . $driver . PHP_EOL);
    exit(2);
}
echo $name;
')" || exit 1

if [[ "$runtime" != "$EXPECTED_DB_DATABASE" ]]; then
    echo "ERROR: configured database name does not match EXPECTED_DB_DATABASE"
    exit 1
fi
if [[ "${CONFIRM_SERVICE_DESK_DEPLOY:-}" != "yes" ]]; then
    echo "Preflight passed: PHP=$("$PHP_BIN" -r 'echo PHP_VERSION;') | database=mysql | expected database matched."
    echo "No changes made. Export CONFIRM_SERVICE_DESK_DEPLOY=yes after backups and a review."
    exit 0
fi

echo "Deploying Service Desk with explicit PHP binary. Verify database backup before continuing."
"$PHP_BIN" "$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
"$PHP_BIN" "$COMPOSER_BIN" check-platform-reqs --no-dev
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan service-desk:install-catalog --force
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan view:cache
echo "Deployment commands completed. Verify HTTP login, ticket workflow, and access controls."
