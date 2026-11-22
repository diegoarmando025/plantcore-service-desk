# PlantCore Service Desk

Multi-department request and ticket tracking for IT, Human Resources, Finance, Purchasing, and Maintenance. An independently developed portfolio application using fictional scenarios, not a commercial multi-tenant SaaS platform.

## Live interactive demo

**[Open Service Desk](https://service-desk.devalejandre.com/login)** and choose **Entrar como administrador demo**. The real Laravel app allows a visitor to create fictional tickets, assign them to five fictional agents, reply, add internal notes, change priorities and statuses, transfer tickets between departments, and see history and dashboard indicators.

All visitors share fictional data in MySQL. An operator-only command can reset demo tickets. Visitor access cannot change user administration or real tickets. This is not a separate app, per-visitor sandbox, or commercial multi-tenant SaaS.

[Interactive reviewer guide](docs/INTERACTIVE_PORTFOLIO_DEMO.md) · [Traceability with REST API](https://traceability.devalejandre.com/login) · [LPA audits](https://lpa.devalejandre.com/login).

## Current stack

- PHP 8.3+ (Hostinger web runtime must be checked separately from SSH)
- Laravel 12 (locked at 12.73.0 in the current release)
- MySQL 8 for CI and the **intended** Service Desk production configuration
- Blade, AdminLTE 4, Bootstrap 5, Bootstrap Icons
- PHPUnit feature tests and GitHub Actions

**Hostinger operator verification (2026-11-22):** Laravel CLI reported MySQL as the default driver, six fictional demo users, seven demo tickets, debug off, and the operator confirmed the site loads. Reverify the web runtime and smoke tests after every deployment.

## Implemented

- Requester and agent authentication
- Tickets with unique codes, priorities, statuses, assignments, comments, internal notes, and activity history
- Department, team, category, and user administration
- Ticket transfer between departments with original department internal notes hidden from the new team
- Role-based and department-scoped access checks
- Locally bundled AdminLTE frontend assets

Not yet implemented: attachments, subtickets, approval workflows, outbound notifications, business-hour SLAs, subscription billing, or multi-tenancy.

## Local setup (dedicated test data only)

1. Run `composer install` using PHP 8.3 or later and verify `composer check-platform-reqs`.
2. Copy `.env.example` to an untracked `.env` and configure a separate MySQL database.
3. Generate the application key once with `php artisan key:generate`.
4. Run `php artisan migrate` after verifying the database, then `php artisan service-desk:install-catalog`.
5. Run `php artisan test`. Do not run any destructive database operation against production.

The GitHub Actions CI uses a disposable MySQL 8 service to exercise the feature tests.

## Hostinger deployment

The application lives outside the web document root in `apps/service-desk`; the public front controller and assets live in the configured subdomain document root. Inspect the actual document root before extraction.

See [deployment instructions](deploy/PUBLICACION.md) and [guarded deployment script](deploy/deploy-hostinger.sh). The script defaults to PHP 8.3 and refuses to change the database until a specific expected MySQL database is confirmed. It never writes `.env`.

Do not upload `.env`, production logs, private files, or database files to GitHub. A passing GitHub workflow is not proof of a successful deployment on Hostinger.
