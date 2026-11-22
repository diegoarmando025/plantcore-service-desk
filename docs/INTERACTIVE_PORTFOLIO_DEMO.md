# Interactive portfolio demo — one Service Desk installation

The demo uses the same Laravel app and MySQL database as the owner. **It is not a separate application or a separate database.**

## User experience

- On \`/login\`, click **Entrar como administrador demo**; the server signs in a dedicated, non-owner **agent** account without publishing a password.
- The user is redirected to \`/guia\` and can use the real dashboard, ticket creation, ticket messages, internal notes, status changes, assignments and department transfers.
- The demo account has membership in each of the five sample departments, and can assign demo tickets to **five fictional agents**, one per department (and to itself). No real operational account may receive demo assignments.
- Demo tickets are tagged \`is_portfolio_demo=1\`. Non-demo accounts (except the owner) cannot list or access them. The demo account cannot list or access ordinary tickets or manage users and configuration.
- Demo users share one account and one dataset: changes are visible to other visitors and may persist until manual reset. Never enter real information. **This is not per-visitor isolation or a production multi-tenant SaaS design.**

## Deployment and activation

1. Make restorable backups of the MySQL database, private \`.env\` and active source. Keep \`APP_DEBUG=false\`.
2. Deploy the tested release into the **existing** app/public roots only, preserving \`.env\`, \`vendor/\`, \`storage/\` and production data. Verify the \`DEPLOYED_COMMIT\` marker and web document root.
3. With PHP 8.3 CLI from the private app root, and after reviewing the additive migration:

\`\`\`bash
/opt/alt/php83/usr/bin/php artisan down --retry=60
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan migrate:status
/opt/alt/php83/usr/bin/php artisan portfolio:provision-demo --force
/opt/alt/php83/usr/bin/php artisan optimize:clear
/opt/alt/php83/usr/bin/php artisan config:cache
/opt/alt/php83/usr/bin/php artisan up
\`\`\`

The provision command requires interactive SSH, one existing \`is_owner=true\` user, five active catalog departments, and at least 15 categories. It refuses conflicting demo accounts or reserved email collisions, confirms backup and target, and inserts fake example records in a transaction. The random account password is never disclosed.

### Manual checks

1. Open \`/login\` in private browser and use the demo button; verify \`/guia\` shows actual tickets.
2. Create, answer, assign and transfer a fictional ticket. Confirm its \`is_portfolio_demo\` marker and MySQL row.
3. Verify the demo account cannot open \`/admin\` or a guessed private ticket.
4. Verify your separate owner login still works, and that \`APP_DEBUG\` is disabled.
5. In MySQL backup review, ensure no real private data was inserted into fictional tickets.

## Reset

\`\`\`bash
/opt/alt/php83/usr/bin/php artisan portfolio:reset-demo --dry-run
/opt/alt/php83/usr/bin/php artisan portfolio:reset-demo
\`\`\`

The interactive reset confirms a verified backup and the exact text \`RESET SERVICE DESK DEMO\`, deletes **only tagged** demo tickets and their messages/activities, and rebuilds the seven demonstration tickets. It never deletes ordinary tickets or user accounts. It is manual; do not claim automatic reset or per-visitor isolation.

## Important limitation

A shared account cannot guarantee visitor-level privacy or protect visitors from each other's demo actions. Current separation safeguards protect operational tickets and owner account, but a future fully multi-tenant commercial version needs organization- or session-scoped isolation.
