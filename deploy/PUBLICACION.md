# PlantCore Service Desk — Hostinger deployment

> Maintained procedure. PHP 8.3 + Laravel 12. The previous notes in this repository were written before the first GitHub import and are no longer accurate.

## Files and configuration

- Private Laravel code: `/home/u641948971/domains/devalejandre.com/apps/service-desk`
- Public application files: `/home/u641948971/domains/devalejandre.com/public_html/service-desk` **only if** hPanel confirms that this is the subdomain document root.
- Environment and data: preserve private `.env`, `storage/`, `vendor/` (unless explicitly updated), uploaded files, and the live database.
- Each deployment must be tied to a specific GitHub commit SHA. Never extract the full Laravel project in the web document root.

## Current database status

On 2026-11-22, the operator reported that SSH `php artisan config:show database.default` returned `sqlite` for Service Desk, LPA, and Traceability. This is a CLI observation, not proof of the PHP web process configuration. Do not run migrations or switch drivers until you have verified the dedicated database and made a recovery backup.

## Read-only checks before applying any update

Use the same PHP binary intended for Service Desk:

```bash
cd /home/u641948971/domains/devalejandre.com/apps/service-desk
/opt/alt/php83/usr/bin/php artisan --version
/opt/alt/php83/usr/bin/php artisan config:show database.default
/opt/alt/php83/usr/bin/php artisan migrate:status
```

The last command reads migration state but can fail if a driver or its credentials are invalid. Do not share secrets or `APP_KEY` in troubleshooting output.

## Guarded deploy script (MySQL only)

Only after a verified backup, correct database configuration and inspection of the ZIP contents:

```bash
cd /home/u641948971/domains/devalejandre.com/apps/service-desk
PHP_BIN=/opt/alt/php83/usr/bin/php EXPECTED_DB_DATABASE=YOUR_SERVICE_DESK_DB bash deploy/deploy-hostinger.sh
```

The default invocation validates the production environment, MySQL connection selection and expected database **without writing to it**. After explicitly reviewing the output and backups, the operator can run:

```bash
PHP_BIN=/opt/alt/php83/usr/bin/php EXPECTED_DB_DATABASE=YOUR_SERVICE_DESK_DB CONFIRM_SERVICE_DESK_DEPLOY=yes bash deploy/deploy-hostinger.sh
```

This second invocation installs dependencies, checks platform requirements, runs non-destructive forward migrations and installs the catalog. Inspect migrations for compatibility and back up the database before executing it. Never use `migrate:fresh`, `db:wipe`, or production demo seeders.

## Smoke tests

Verify HTTPS, login/logout, ticket creation, assignment, messages, department transfer, internal note isolation and admin controls. Check that `/.env`, `/vendor/autoload.php` and `/storage/logs` are not publicly served.

## Recovery

Restore the prior verified release and application data from backups. Laravel database migration rollback is not automatically safe: analyze schema changes and use the database backup where required. Check the PHP web handler separately from the CLI binary. The installer does not assert that Hostinger web PHP was changed.
