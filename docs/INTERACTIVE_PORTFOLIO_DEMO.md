# Interactive demo and technical review

PlantCore Service Desk uses a shared fictional dataset so reviewers can exercise the application without creating accounts or requesting credentials. The sample environment uses the same Laravel controllers, authorization rules, database models, and reporting logic as the rest of the application.

## Evaluation walkthrough

1. Open [the live demo](https://service-desk.devalejandre.com/login) and select **Entrar como administrador demo**.
2. Review the dashboard and identify outstanding, overdue, and completed tickets.
3. Create a service request and confirm that it appears in the inbox and Kanban board.
4. Open a ticket, post a reply, and add an internal note.
5. Change its priority or workflow status and assign it to an agent within the selected department.
6. Transfer the ticket to another department and inspect its recorded activity.
7. Upload and download a permitted attachment on a fictional ticket.
8. Open the reporting screen, change the date range, and export the results to CSV.

The user interface is intentionally presented in Spanish. These documentation and source-control records are written in English.

## Sample data

The dataset includes five fictional agents for each of five departments and 25 example tickets. It covers assigned, unassigned, in-progress, waiting, and resolved requests. Deadline calculations use each ticket's original creation timestamp and its category's elapsed-hour SLA.

Sample agent identities are not public login accounts. The shared visitor account is limited to fictional tickets and cannot access user administration.

## Security boundaries

- Authentication is required for ticket management, dashboards, and reports.
- Ticket read and write permissions are checked against role, department membership, and demo classification.
- Internal notes are not exposed to ordinary requesters or to an unrelated destination department after a ticket transfer.
- Attachments are stored on a private disk and served through authorized controller actions, not public file URLs.
- The owner identity is protected from deletion by application-level safeguards.
- Reset commands operate on explicitly marked fictional tickets only and require an operator confirmation.

The demo is shared rather than isolated by browser session. Changes may be visible to other visitors until the sample dataset is restored. Never submit real personal or confidential information.

## Technical structure

| Component | Responsibility |
| --- | --- |
| `TicketController` | Ticket creation, discussion, assignment, status, and transfers |
| `TicketAttachmentController` | Validated private evidence uploads and downloads |
| `ReportsController` | Date-scoped workload, resolution, SLA calculations, and CSV |
| `KanbanController` | Status-based board and authorized transitions |
| `AdminController` | Departments, teams, categories, accounts, and memberships |
| `TicketAccess` | Shared ticket visibility and modification rules |
| `PortfolioDemoScenario` | Provisioning and restoration of fictional records |
| `tests/Feature` | Workflow, permission, UI, report, and attachment regression tests |

## Running tests

From a development checkout with a disposable database configured:

```bash
composer install
php artisan test
```

GitHub Actions also runs the feature suite against MySQL 8. Tests are required before release publication.

## Limitations

This application does not implement tenant-level data isolation, outbound messaging integrations, subscription management, or business-calendar-based SLAs. All sample SLA values represent elapsed hours.

The sample scenario is a demonstration dataset and not a record of actual business incidents.
