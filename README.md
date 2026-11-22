# DevAlejandre Service Desk

A Laravel-based service desk for handling requests across IT, Human Resources, Finance, Purchasing, and Maintenance. The project demonstrates ticket lifecycle management, department-scoped authorization, reporting, and a working interactive demo.

## Try the application

[Open the interactive demo](https://service-desk.devalejandre.com/login) and select **Entrar al demo interactivo**. No sample credentials are required.

The application includes a resettable fictional dataset: 25 service requests and 25 sample agents across five departments. Visitors can create and update requests, post replies and internal notes, assign agents, transfer requests, attach evidence, and inspect operational reports.

Demo visitors share the same sample account and data. Do not enter confidential or personal information. Demo data is isolated from operational records, but individual visitors do not receive separate workspaces.

## Technology

- PHP 8.3 and Laravel 12
- MySQL 8
- Blade, AdminLTE 4, Bootstrap 5, Bootstrap Icons
- PHPUnit feature tests
- GitHub Actions for automated testing and release verification

## Features

- Requester and agent authentication with a password-free sample entry point
- Ticket creation, assignment, priority management, status changes, and history
- Department and team membership controls
- Department transfers with restricted visibility of previous internal notes
- Private file attachments with validated types, file-size limits, and authorized downloads
- Dashboard, Kanban board, department and agent workload reporting
- Elapsed-time SLA measurement and CSV exports
- Administration of users, departments, teams, and service categories
- Protected owner account and separate access rules for the shared demo dataset
- Guarded commands to provision and restore fictional sample records

The application is an implementation of a single-organization service desk, not a multi-tenant SaaS product. It does not implement email notifications, subscription billing, or business-hour SLA schedules.

## Architecture

The application follows Laravel's MVC conventions:

- **Controllers** handle HTTP input, validation, and workflow actions.
- **Eloquent models and relationships** represent users, service requests, departments, teams, messages, activities, and attachments.
- **Access rules** restrict ticket visibility and modification by role, department membership, and demo data classification.
- **Database transactions** protect multi-step updates such as ticket transfers and demo restoration.
- **Blade templates** render server-side interfaces using AdminLTE and Bootstrap.
- **Feature tests** exercise authentication, permissions, ticket workflows, attachments, reports, and demo isolation.

[Architecture and evaluation notes](docs/INTERACTIVE_PORTFOLIO_DEMO.md)

## Local development

Use a dedicated development database and never copy production credentials into the repository.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan service-desk:install-catalog
php artisan test
```

Configure the database connection in the local `.env` before running migrations. The automated test workflow uses a disposable MySQL service.

## Related projects

- [Industrial Traceability](https://github.com/diegoarmando025/industrial-traceability-api)
- [Layered Process Audits](https://github.com/diegoarmando025/plantcore-lpa)

## Project scope

This repository contains an independently developed portfolio application and fictional test scenarios. It is intended to demonstrate working engineering decisions and implementation rather than represent a deployed commercial customer system.
