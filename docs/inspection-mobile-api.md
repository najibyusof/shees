# Inspection Mobile API

## Base Path

- `/api/v1/inspection`

## Authentication

- Protected endpoints require `Authorization: Bearer <token>`.
- Get token with `POST /auth/login`.
- Revoke current token with `POST /auth/logout`.
- List active sessions with `GET /auth/sessions`.
- Revoke a specific session with `POST /auth/sessions/{mobileAccessToken}/revoke`.
- Rename a specific session with `PATCH /auth/sessions/{mobileAccessToken}`.
- Rotate current token with `POST /auth/rotate`.

Rate limits:

- `POST /auth/login`: 10 requests/minute.
- `POST /auth/rotate`: 20 requests/minute.
- `POST /sync/upload`: 120 requests/minute.

### Login Request

```json
{
    "email": "inspector@example.com",
    "password": "password",
    "device_name": "android-warehouse-01",
    "ttl_minutes": 10080
}
```

### Login Response

```json
{
    "data": {
        "token": "plain-token",
        "token_type": "Bearer",
        "session_id": 12,
        "device_name": "android-warehouse-01",
        "expires_at": "2026-04-03T12:00:00Z",
        "user": {
            "id": 1,
            "name": "Inspector",
            "email": "inspector@example.com"
        }
    }
}
```

## Inspection Endpoints

- `GET /checklists`
- `POST /runs`
- `GET /runs/{inspection}`
- `PUT /runs/{inspection}/responses`
- `POST /runs/{inspection}/submit`

Ownership enforcement:

- `runs/{inspection}` read/update/submit is restricted to the inspection owner (`inspector_id`).

## Sync Endpoints

- `GET /sync/contract`
- `POST /sync/upload`
- `GET /sync/metrics`
- `GET /sync/pending`
- `POST /sync/jobs/{inspectionSyncJob}/ack`
- `POST /sync/jobs/{inspectionSyncJob}/conflict`
- `POST /sync/conflicts/{inspectionSyncConflict}/resolve`

### Contract Handshake Response

`GET /sync/contract`

```json
{
    "data": {
        "name": "inspection-sync",
        "version": 1,
        "capabilities": {
            "upload": true,
            "download": true,
            "auto_merge": ["inspection_response", "inspection_image"]
        },
        "server_time": "2026-03-27T12:00:00Z"
    }
}
```

### Upload Contract Requirements

`POST /sync/upload` must include:

- `contract_name` = `inspection-sync`
- `contract_version` = `1`

Requests with unsupported contract name/version return `422` validation errors.

Idempotency support:

- Optional `idempotency_key` can be passed in upload payloads.
- Same user + device + idempotency key returns the original job instead of creating a duplicate.
- Replay responses return `200` with:

```json
{
    "meta": {
        "idempotency_replay": true
    }
}
```

First-time submissions return `201` with `idempotency_replay = false`.

### Sync Telemetry

`GET /sync/metrics` returns aggregate processing telemetry:

- job totals and status counts,
- open conflict count,
- idempotent job count,
- processing latency summary (`average`, `min`, `max`).

### Identity Rules

- Upload job `user_id` is derived from the authenticated token user.
- Conflict resolution `resolved_by` is derived from the authenticated token user.
- Client-supplied values for those identity fields are not trusted.
