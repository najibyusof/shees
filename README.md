# SHEES

SHEES is a Laravel 12 application for managing safety, health, environment, and field operations workflows. The codebase includes incident reporting, inspections, worker and attendance tracking, training records, offline synchronization support, and OpenAPI documentation for the mobile/API surface.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL or another Laravel-supported database
- Vite + Tailwind CSS
- Swagger UI via `darkaonline/l5-swagger`

## Core Modules

- Incident management with comments, attachments, approvals, and activity tracking
- Inspection checklists and mobile inspection API flows
- Training and certificate tracking
- Worker and attendance logging
- Audit and corrective action support
- Offline sync endpoints with conflict handling
- Generated OpenAPI/Swagger documentation

## Local Setup

1. Install PHP dependencies:

    ```bash
    composer install
    ```

2. Install frontend dependencies:

    ```bash
    npm install
    ```

3. Create the environment file if needed:

    ```bash
    copy .env.example .env
    ```

4. Generate the application key:

    ```bash
    php artisan key:generate
    ```

5. Configure your database connection in `.env`.

6. Run migrations:

    ```bash
    php artisan migrate
    ```

7. Start the application:

    ```bash
    composer run dev
    ```

This runs the Laravel server, queue listener, log tailing, and Vite dev server together.

## Useful Commands

```bash
php artisan test
php artisan route:list
php artisan swagger:generate
npm run build
```

## Demo Analytics Dataset Setup

For a clean demo database with realistic analytics trends, run seeders in this order:

```bash
php artisan migrate:fresh
php artisan db:seed --class=SystemBaselineSeeder
php artisan db:seed --class=DemoSampleDataSeeder
```

Why this order:

- `SystemBaselineSeeder` creates RBAC roles/permissions and required lookup/reference data.
- `DemoSampleDataSeeder` then builds analytics-heavy sample data with role-aware visibility.
- Incident volumes are constrained to demo-safe ranges (target ~170, hard cap 200).

## API Documentation

- Swagger UI: `http://localhost/api/documentation`
- OpenAPI JSON: `http://localhost/api/documentation/json`
- Quick reference: `API_QUICK_REFERENCE.md`
- Detailed Swagger notes: `SWAGGER_SETUP.md`
- Inspection mobile API notes: `docs/inspection-mobile-api.md`

## Authentication

API authentication uses Bearer tokens. All protected routes require `Authorization: Bearer <token>`.

```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "local-dev"
  }'
```

The response contains a `token` field. Pass it as `Authorization: Bearer <token>` on every subsequent request.

## API Modules

All V1 routes are prefixed `/api/v1/` and protected by `mobile.token` middleware (except login).

| Module      | Base path             | Notes                                |
| ----------- | --------------------- | ------------------------------------ |
| Auth        | `/api/v1/auth/`       | Login, logout, current user          |
| Incidents   | `/api/v1/incidents`   | Full CRUD + workflow pipeline        |
| Trainings   | `/api/v1/trainings`   | Training record CRUD                 |
| Inspections | `/api/v1/inspections` | Inspection CRUD                      |
| Site Audits | `/api/v1/audits`      | Audit CRUD                           |
| NCR Reports | `/api/v1/ncr`         | Non-conformance report CRUD          |
| Workers     | `/api/v1/workers`     | Worker CRUD + attendance logging     |
| Users       | `/api/v1/users`       | User listing                         |
| Device      | `/api/v1/device/`     | Device registration / deregistration |
| Sync        | `/api/v1/sync`        | General offline sync                 |

### Incident workflow endpoints

Beyond standard CRUD, incidents have a comment-driven approval pipeline:

| Method  | Path                                         | Description                            |
| ------- | -------------------------------------------- | -------------------------------------- |
| `GET`   | `/api/v1/incidents/{id}/allowed-transitions` | States the incident can move to        |
| `POST`  | `/api/v1/incidents/{id}/transition`          | Advance or reject the incident         |
| `POST`  | `/api/v1/incidents/{id}/comments`            | Add a comment (supports `is_critical`) |
| `POST`  | `/api/v1/comments/{id}/reply`                | Reply to a comment                     |
| `PATCH` | `/api/v1/comments/{id}/resolve`              | Resolve or un-resolve a comment        |

Workflow statuses: `draft` → `draft_submitted` → `draft_reviewed` → `final_submitted` → `final_reviewed` → `pending_closure` → `closed`.

See `API_QUICK_REFERENCE.md` for full request/response shapes.

## Offline Sync

The platform supports offline-first mobile clients. Sync requests carry device identity, a conflict resolution strategy, and locally buffered records.

- General sync: `POST /api/v1/sync`
- Inspection-specific sync (upload, acknowledge, conflict): `POST /api/v1/inspection/sync/upload` — see `docs/inspection-mobile-api.md`

Records created offline should include a `temporary_id` (UUID) and `local_created_at` timestamp so the server can reconcile them after reconnect.

## Testing

Run the automated test suite with:

```bash
php artisan test
```

If you are changing API behavior, regenerate Swagger output and verify the affected routes in Swagger UI.

### Factory Scenario Recipes

The test factories now include reusable scenario states for audit/NCR workflows.

NCR examples:

```php
use App\Models\NcrReport;

// High-severity open NCR
$ncr = NcrReport::factory()->highSeverityOpen()->create();

// Overdue NCR still open
$overdueNcr = NcrReport::factory()->overdueOpen()->create();

// NCR waiting verification
$pendingVerificationNcr = NcrReport::factory()->pendingVerification()->create();

// Closed NCR with verification/closure timestamps
$closedNcr = NcrReport::factory()->closed()->create();
```

Corrective action examples:

```php
use App\Models\CorrectiveAction;

// Lifecycle states
$openAction = CorrectiveAction::factory()->open()->create();
$inProgressAction = CorrectiveAction::factory()->inProgress()->create();
$overdueAction = CorrectiveAction::factory()->overdue()->create();
$completedAction = CorrectiveAction::factory()->completed()->create();
$verifiedAction = CorrectiveAction::factory()->verified()->create();
```

Composed example (audit -> ncr -> corrective action):

```php
use App\Models\CorrectiveAction;
use App\Models\NcrReport;
use App\Models\SiteAudit;

$audit = SiteAudit::factory()->create();

$ncr = NcrReport::factory()
    ->for($audit)
    ->highSeverityOpen()
    ->create();

$action = CorrectiveAction::factory()
    ->for($ncr)
    ->overdue()
    ->create();
```

## Repository Notes

- `vendor/`, `node_modules/`, `.env`, and generated build artifacts are already ignored by `.gitignore`.
- Generated API docs currently live in `storage/api-docs/`.

## License

This project is distributed under the MIT license unless your organization applies different internal usage terms.
