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

## API Documentation

- Swagger UI: `http://localhost/api/documentation`
- OpenAPI JSON: `http://localhost/api/documentation/json`
- Quick reference: `API_QUICK_REFERENCE.md`
- Detailed Swagger notes: `SWAGGER_SETUP.md`
- Inspection mobile API notes: `docs/inspection-mobile-api.md`

## Authentication

API authentication uses Bearer tokens. Typical login flow:

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
	 "email": "user@example.com",
	 "password": "password123",
	 "device_name": "local-dev"
  }'
```

Use the returned token in the `Authorization: Bearer <token>` header for protected routes.

## Offline Sync

The platform includes sync endpoints intended for mobile or intermittently connected clients. Sync requests support device identity, conflict strategy selection, and reconciliation of locally created records after reconnect.

See `POST /api/sync` and the inspection sync contract in `docs/inspection-mobile-api.md` for request and response details.

## Testing

Run the automated test suite with:

```bash
php artisan test
```

If you are changing API behavior, regenerate Swagger output and verify the affected routes in Swagger UI.

## Repository Notes

- `vendor/`, `node_modules/`, `.env`, and generated build artifacts are already ignored by `.gitignore`.
- Generated API docs currently live in `storage/api-docs/`.

## License

This project is distributed under the MIT license unless your organization applies different internal usage terms.
