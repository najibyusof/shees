# SHEES API — Quick Reference

**Base URL:** `http://localhost/api/v1`
**Auth:** `Authorization: Bearer <token>` on all protected routes
**Swagger UI:** `http://localhost/api/documentation`

---

## Authentication

| Method | Path                  | Description                   |
| ------ | --------------------- | ----------------------------- |
| `POST` | `/api/v1/auth/login`  | Login — returns Bearer token  |
| `POST` | `/api/v1/auth/logout` | Logout (revoke current token) |
| `GET`  | `/api/v1/user`        | Get authenticated user        |

### Login request

```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "MyPhone"
  }'
```

Response: `{ "token": "..." }`

---

## Incidents — CRUD

All paths protected by `mobile.token`.

| Method   | Path                     | Description                |
| -------- | ------------------------ | -------------------------- |
| `GET`    | `/api/v1/incidents`      | Paginated incident list    |
| `POST`   | `/api/v1/incidents`      | Create incident            |
| `GET`    | `/api/v1/incidents/{id}` | Get incident (full detail) |
| `PUT`    | `/api/v1/incidents/{id}` | Update incident            |
| `DELETE` | `/api/v1/incidents/{id}` | Soft-delete incident       |

### GET /api/v1/incidents — query parameters

| Parameter        | Type    | Description                                             |
| ---------------- | ------- | ------------------------------------------------------- |
| `status`         | string  | Filter by workflow status key (see status values below) |
| `classification` | string  | Filter by classification code                           |
| `search`         | string  | Full-text search across title and description           |
| `from`           | date    | `incident_date` range start (`YYYY-MM-DD`)              |
| `to`             | date    | `incident_date` range end (`YYYY-MM-DD`)                |
| `sort`           | string  | Column to sort by (default: `created_at`)               |
| `direction`      | string  | `asc` or `desc` (default: `desc`)                       |
| `per_page`       | integer | Results per page (default: 15)                          |

### POST /api/v1/incidents — required body fields

```json
{
    "title": "Scaffold collapse at Block A",
    "incident_type_id": 1,
    "incident_date": "2026-03-28",
    "incident_time": "14:30",
    "work_package_id": 2,
    "location_type_id": 1,
    "location_id": 3,
    "classification_id": 2,
    "incident_description": "Detailed description of what happened.",
    "immediate_response": "Area cordoned off, first aid given.",
    "work_activity_id": 4,
    "attachments": [{ "file": "<binary>", "attachment_type_id": 1 }]
}
```

Optional top-level fields: `other_location`, `reclassification_id`, `subcontractor_id`, `person_in_charge`, `subcontractor_contact_number`, `gps_location`, `activity_during_incident`, `type_of_accident`, `basic_effect`, `conclusion`, `close_remark`, `rootcause_id`, `other_rootcause`, `temporary_id`, `local_created_at`.

Optional nested arrays: `chronologies[]`, `victims[]`, `witnesses[]`.

### Update notes

- **Admin / Manager / Safety Officer** can `PUT` any incident.
- **Reporter** can `PUT` only incidents in `draft` or `rejected` status.
- Use `PATCH /api/v1/comments/{id}/resolve` to mark comments resolved (not part of the PUT body).

---

## Incidents — Workflow

These endpoints drive the approval pipeline. All require `mobile.token`.

| Method  | Path                                         | Description                           |
| ------- | -------------------------------------------- | ------------------------------------- |
| `GET`   | `/api/v1/incidents/{id}/allowed-transitions` | List states the incident can move to  |
| `POST`  | `/api/v1/incidents/{id}/transition`          | Advance or reject the incident status |
| `POST`  | `/api/v1/incidents/{id}/comments`            | Add a comment to an incident          |
| `POST`  | `/api/v1/comments/{id}/reply`                | Reply to an existing comment          |
| `PATCH` | `/api/v1/comments/{id}/resolve`              | Mark a comment resolved / unresolved  |

### GET /api/v1/incidents/{id}/allowed-transitions

Returns the transitions available to the current user for this incident.

```json
[
    {
        "status": "draft_submitted",
        "label": "Submit Draft",
        "blocked_by_unresolved_critical_comments": false
    }
]
```

`blocked_by_unresolved_critical_comments: true` means a critical comment must be resolved before this transition is allowed.

### POST /api/v1/incidents/{id}/transition

```json
{
    "to_status": "draft_submitted",
    "remarks": "All sections complete."
}
```

`remarks` is optional. `to_status` must be one of the valid workflow statuses.

**Response:**

```json
{
    "message": "Incident transitioned to Draft Submitted.",
    "incident_id": 42,
    "status": "draft_submitted",
    "status_label": "Draft Submitted"
}
```

#### Valid `to_status` values

| Value             | Label           |
| ----------------- | --------------- |
| `draft`           | Draft           |
| `draft_submitted` | Draft Submitted |
| `draft_reviewed`  | Draft Reviewed  |
| `final_submitted` | Final Submitted |
| `final_reviewed`  | Final Reviewed  |
| `pending_closure` | Pending Closure |
| `closed`          | Closed          |

### POST /api/v1/incidents/{id}/comments

```json
{
    "comment": "Please attach the JSA document.",
    "comment_type": "action_required",
    "is_critical": true
}
```

`comment_type` defaults to `general`. `is_critical` defaults to `false`.

#### Valid `comment_type` values

| Value             | Use case                                     |
| ----------------- | -------------------------------------------- |
| `general`         | General remark                               |
| `clarification`   | Request for clarification                    |
| `action_required` | Blocker — must be resolved before transition |
| `action`          | Corrective action note                       |
| `review`          | Review observation                           |
| `investigation`   | Investigation finding                        |

### POST /api/v1/comments/{id}/reply

```json
{ "reply": "JSA attached above." }
```

### PATCH /api/v1/comments/{id}/resolve

```json
{
    "resolved": true,
    "resolution_note": "JSA reviewed and accepted."
}
```

`resolution_note` is optional. Set `resolved: false` to un-resolve a comment.

**Response:**

```json
{
    "message": "Comment resolved.",
    "comment_id": 17,
    "is_resolved": true,
    "resolved_at": "2026-03-29T10:15:00Z",
    "resolved_by": 5
}
```

---

## Other Modules

| Method   | Path                              | Description            |
| -------- | --------------------------------- | ---------------------- |
| `GET`    | `/api/v1/trainings`               | List training records  |
| `POST`   | `/api/v1/trainings`               | Create training record |
| `GET`    | `/api/v1/trainings/{id}`          | Get training record    |
| `PUT`    | `/api/v1/trainings/{id}`          | Update training record |
| `DELETE` | `/api/v1/trainings/{id}`          | Delete training record |
| `GET`    | `/api/v1/inspections`             | List inspections       |
| `POST`   | `/api/v1/inspections`             | Create inspection      |
| `GET`    | `/api/v1/inspections/{id}`        | Get inspection         |
| `PUT`    | `/api/v1/inspections/{id}`        | Update inspection      |
| `DELETE` | `/api/v1/inspections/{id}`        | Delete inspection      |
| `GET`    | `/api/v1/audits`                  | List site audits       |
| `POST`   | `/api/v1/audits`                  | Create audit           |
| `GET`    | `/api/v1/audits/{id}`             | Get audit              |
| `PUT`    | `/api/v1/audits/{id}`             | Update audit           |
| `DELETE` | `/api/v1/audits/{id}`             | Delete audit           |
| `GET`    | `/api/v1/ncr`                     | List NCR reports       |
| `POST`   | `/api/v1/ncr`                     | Create NCR report      |
| `GET`    | `/api/v1/ncr/{id}`                | Get NCR report         |
| `PUT`    | `/api/v1/ncr/{id}`                | Update NCR report      |
| `DELETE` | `/api/v1/ncr/{id}`                | Delete NCR report      |
| `GET`    | `/api/v1/workers`                 | List workers           |
| `POST`   | `/api/v1/workers`                 | Create worker          |
| `GET`    | `/api/v1/workers/{id}`            | Get worker             |
| `PUT`    | `/api/v1/workers/{id}`            | Update worker          |
| `DELETE` | `/api/v1/workers/{id}`            | Delete worker          |
| `POST`   | `/api/v1/workers/{id}/attendance` | Log worker attendance  |
| `GET`    | `/api/v1/users`                   | List users             |
| `GET`    | `/api/v1/users/{id}`              | Get user               |

---

## Device Registration

| Method   | Path                           | Description                      |
| -------- | ------------------------------ | -------------------------------- |
| `POST`   | `/api/v1/device/register`      | Register device for push/offline |
| `GET`    | `/api/v1/device/registrations` | List registered devices          |
| `DELETE` | `/api/v1/device/{deviceId}`    | Deregister a device              |

---

## Offline Sync

**Endpoint:** `POST /api/v1/sync`  
Rate-limited: 60 requests / minute.

```json
{
    "device_id": "device-unique-id",
    "last_synced_at": "2026-03-27T08:00:00Z",
    "conflict_strategy": "last_updated_wins",
    "data": {
        "incidents": [
            {
                "temporary_id": "550e8400-e29b-41d4-a716-446655440000",
                "title": "Near-miss at Gate 2",
                "incident_date": "2026-03-28",
                "incident_time": "09:45",
                "local_created_at": "2026-03-28T09:46:00Z"
            }
        ]
    }
}
```

Response fields: `synced_records`, `server_updates`, `conflicts`.

For the inspection-specific sync contract (upload, acknowledge, conflict resolution), see `docs/inspection-mobile-api.md`.

---

## Response Envelope

```json
{
    "data": {},
    "message": "Operation successful.",
    "meta": { "current_page": 1, "total": 64 }
}
```

Errors return HTTP 4xx/5xx with:

```json
{
    "message": "The given data was invalid.",
    "errors": { "title": ["The title field is required."] }
}
```

---

## Useful Commands

```bash
php artisan route:list --path=api/v1
php artisan swagger:generate
php artisan cache:clear && php artisan config:cache
```

Swagger UI: `http://localhost/api/documentation`  
OpenAPI JSON: `http://localhost/api/documentation/json`
