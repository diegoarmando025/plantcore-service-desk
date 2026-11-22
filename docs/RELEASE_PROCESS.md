# Reproducible Service Desk releases

All repository-facing documentation and Git messages use English. Customer-facing application labels may remain Spanish.

## Release contract

- A GitHub release is built **only after** the `Laravel MySQL CI` workflow succeeds on a push to `main`.
- The release checkout uses the exact tested 40-character commit SHA, not the current branch head.
- `scripts/package-hostinger.sh` selects tracked code, adds the separate public document root and places the exact commit in `apps/service-desk/DEPLOYED_COMMIT`.
- Each release contains `PlantCore_Service_Desk_<12-digit-sha>.zip` and its SHA-256 checksum.
- Both `apps/service-desk/` (private Laravel source) and `public_html/service-desk/` (front controller and browser assets) must be extracted relative to the appropriate domain directory **only after verifying hPanel document root**.
- The archive contains no `.env`, `vendor/`, SQLite databases, runtime logs, user-uploaded storage, or MySQL backup.
- Existing `.env`, `storage/`, `vendor/`, and database records must be preserved. Install dependencies separately when the lockfile changes, using the correct PHP interpreter.

## CI package test

The same packaging script runs inside CI, before publishing, with source commit SHA. It creates only a temporary archive under the CI checkout and does not deploy or migrate anything.

## Database warning

At the last operator check, `php artisan config:show database.default` returned `sqlite` for Service Desk. Do not run the MySQL-only guarded deployment or point the application at an empty database until the runtime database and backups have been reconciled. This is not solved by a successful CI run.

## Hostinger verification

After deployment, inspect `apps/service-desk/DEPLOYED_COMMIT` and compare it with the release SHA. Verify the browser PHP version, active DB driver, migrations, and login in the actual web process. No claim of production synchronization is made until those checks are performed.
