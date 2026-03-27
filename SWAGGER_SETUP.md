# SHEES API - Swagger/OpenAPI Documentation Setup Guide

## Overview

The SHEES API now includes comprehensive Swagger/OpenAPI documentation with an interactive Swagger UI. This allows developers to:

- ✅ Explore all API endpoints
- ✅ Test endpoints directly from the browser
- ✅ Authenticate with Bearer tokens
- ✅ View request/response schemas
- ✅ Understand offline sync capabilities

---

## Installation Summary

The following has been completed:

### 1. ✅ Package Installation

```bash
composer require darkaonline/l5-swagger
```

**Installed dependencies:**

- `darkaonline/l5-swagger` (v11.0.0)
- `zircote/swagger-php` (v6.0.6)
- `swagger-api/swagger-ui` (v5.32.1)

### 2. ✅ Service Provider Registration

Added `L5Swagger\L5SwaggerServiceProvider::class` to `bootstrap/providers.php`

### 3. ✅ Configuration

Created `config/l5-swagger.php` with:

- API Title: "SHEES API"
- API Version: v1
- Base Path: /api
- Documentation routes: `/api/documentation` and `/api/documentation/json`
- Security scheme: Bearer token (JWT)

### 4. ✅ OpenAPI Annotations

Created comprehensive annotation files:

- **`app/OpenAPI/OpenAPIDoc.php`** - Base OpenAPI configuration
- **`app/OpenAPI/IncidentEndpoints.php`** - Incident CRUD endpoints
- **`app/OpenAPI/SyncEndpoints.php`** - Offline sync endpoint with detailed explanation
- **`app/OpenAPI/OtherEndpoints.php`** - Users, Training, Inspections, Audits, Workers, Attendance
- **`app/Http/Controllers/Api/V1/AuthControllerDocumented.php`** - Annotated Auth example

### 5. ✅ Documentation Generation

Created `storage/api-docs/api-docs.json` with full OpenAPI 3.0 specification

### 6. ✅ UI Implementation

- **Controller:** `app/Http/Controllers/Api/SwaggerController.php`
- **View:** `resources/views/swagger.blade.php`
- **Routes:** Added to `routes/api.php`
    - `GET /api/documentation` - Swagger UI
    - `GET /api/documentation/json` - OpenAPI JSON

---

## Accessing the Documentation

### 📚 Swagger UI (Interactive)

Visit: **`http://localhost/api/documentation`**

Features:

- ✨ Interactive endpoint testing
- 🔒 Bearer token authentication
- 📋 Request/response examples
- 🔍 Full endpoint documentation
- 💾 Persistent token storage

### 📄 OpenAPI JSON

Visit: **`http://localhost/api/documentation/json`**

Raw OpenAPI 3.0 specification for integration with other tools.

---

## Authentication in Swagger UI

### Step 1: Get a Bearer Token

1. Click the **"Authorize"** button (top right)
2. Use the **`/api/auth/login`** endpoint to get a token:

```json
{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "Swagger UI"
}
```

Response:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "your_bearer_token_here",
    "token_type": "Bearer",
    "expires_at": "2026-04-04T10:30:00Z",
    "user": { ... }
  }
}
```

### Step 2: Authorize in Swagger

1. Copy the **`token`** value from response
2. In the "Authorize" dialog, enter: `your_bearer_token_here`
3. Click **"Authorize"**
4. Now all protected endpoints will include the token

### Step 3: Test Protected Endpoints

All endpoints in the UI will now include the `Authorization: Bearer your_token` header.

---

## API Endpoints Overview

### 🔐 Authentication

- `POST /api/auth/login` - User login (returns Bearer token)
- `POST /api/auth/logout` - Revoke token
- `GET /api/auth/user` - Get current user profile

### 📋 Incidents

- `GET /api/incidents` - List incidents (paginated, filterable)
- `POST /api/incidents` - Create incident
- `GET /api/incidents/{id}` - Get incident details
- `PUT /api/incidents/{id}` - Update incident
- `DELETE /api/incidents/{id}` - Delete incident

### 📚 Training

- `GET /api/training` - List training records
- `POST /api/training` - Create training
- (CRUD endpoints follow standard REST pattern)

### 🔍 Inspections

- `GET /api/inspections` - List inspections
- `POST /api/inspections` - Create inspection
- (Full CRUD support)

### 👥 Workers & Attendance

- `GET /api/workers` - List workers
- `POST /api/attendance` - Record attendance
- `GET /api/attendance` - List attendance logs

### 📊 Audits & NCR

- `GET /api/audit-logs` - List audit logs with filtering
- `GET /api/ncr-reports` - List NCR reports
- `POST /api/ncr-reports` - Create NCR report

### 🔄 Offline Sync (Critical Feature)

**Endpoint:** `POST /api/sync`

This is the core endpoint for offline-first mobile applications.

#### Key Concepts:

##### 1. **temporary_id** (Offline Record Identification)

When users create/modify records offline without a server ID:

- Client generates a temporary_id (UUID format)
- Used to track record locally
- On sync, server returns assigned server ID
- Client maps temporary_id → server_id for future operations

**Example:**

```json
{
    "temporary_id": "a7f2c3e5-4d9a-8f2c-3e5d-9a8f2c3e5d9a",
    "title": "Warehouse incident",
    "local_created_at": "2026-03-28T14:20:00Z"
}
```

##### 2. **local_created_at / local_updated_at**

Timestamps from the client device to detect conflicts:

- From offline-created: `local_created_at`
- From offline-modified: `local_updated_at`
- Help determine conflict winners

##### 3. **Conflict Resolution Strategies**

**a) last_updated_wins** (Default)

- Compares timestamps of last modification
- Latest change takes precedence
- Best for: Status updates, incident modifications

**b) client_wins**

- Client data always overrides server
- Best for: Field inspection data (captured on-site)
- Mobile app is authoritative

**c) server_wins**

- Server data always takes precedence
- Best for: Approval systems, policy enforcement
- Server is authoritative

##### 4. **Complete Sync Flow**

```
Client (Offline)        Server
    |                    |
    |--[1] Collect changes
    |    - New: incidents with temporary_id
    |    - Modified: existing records with server id
    |
    |--[2] Send: POST /api/sync
    |    { device_id, last_synced_at, data }
    |
    |                [3] Server processes:
    |                    - Validates data
    |                    - Detects conflicts
    |                    - Resolves using strategy
    |                    - Queries all changes
    |
    |<--[4] Response
    |    - synced_records: with assigned IDs
    |    - server_updates: all changes since last_synced_at
    |    - conflicts: any unresolved conflicts
    |
    |--[5] Client applies:
    |    - Map temp_id → server_id
    |    - Update local DB with server changes
    |    - Display conflicts for user review
```

##### 5. **Example Sync Request**

```json
{
  "device_id": "device-uuid-123",
  "last_synced_at": "2026-03-27T08:00:00Z",
  "conflict_strategy": "last_updated_wins",
  "data": {
    "incidents": [
      {
        "temporary_id": "temp-uuid-1",
        "title": "Spill in warehouse",
        "status": "reported",
        "classification": "high_risk",
        "location": "Warehouse A",
        "datetime": "2026-03-28T14:30:00Z",
        "local_created_at": "2026-03-28T14:20:00Z"
      }
    ],
    "attendance_logs": [ ... ],
    "inspections": [ ... ]
  }
}
```

##### 6. **Example Sync Response**

```json
{
    "success": true,
    "message": "Sync completed successfully.",
    "data": {
        "server_time": "2026-03-28T15:45:00Z",
        "synced_records": {
            "incidents": [
                {
                    "temporary_id": "temp-uuid-1",
                    "id": 42,
                    "created_at": "2026-03-28T14:31:00Z",
                    "updated_at": "2026-03-28T14:31:00Z"
                }
            ]
        },
        "server_updates": {
            "incidents": [
                {
                    "id": 40,
                    "status": "resolved",
                    "approved_at": "2026-03-28T15:30:00Z"
                },
                { "id": 41, "comment_count": 2 }
            ]
        },
        "conflicts": []
    },
    "meta": {
        "server_time": "2026-03-28T15:45:00Z",
        "conflict_count": 0,
        "conflict_strategy": "last_updated_wins"
    }
}
```

---

## Response Format Standard

All API responses follow this structure:

### Success Response (200/201)

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "meta": { ... }
}
```

### Error Response (4xx/5xx)

```json
{
    "success": false,
    "message": "Error message",
    "errors": { "field": ["error details"] }
}
```

### Paginated Response

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```

---

## Adding New Endpoints Documentation

To document new endpoints, use OpenAPI annotations:

### Example: Simple GET Endpoint

```php
use OpenAPI\Annotations as OA;

class YourController {
    /**
     * @OA\Get(
     *     path="/api/resource",
     *     operationId="listResources",
     *     tags={"Resource"},
     *     summary="List all resources",
     *     description="Retrieve a paginated list of resources",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resources retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        // Implementation
    }
}
```

### Example: POST with Request Body

```php
/**
 * @OA\Post(
 *     path="/api/resource",
 *     operationId="createResource",
 *     tags={"Resource"},
 *     summary="Create resource",
 *     security={{"bearer_token": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "email"},
 *             @OA\Property(property="name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", format="email")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Resource created"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
public function store(Request $request)
{
    // Implementation
}
```

---

## Regenerating Documentation

### Option 1: Using L5Swagger Command

If the artisan command works:

```bash
php artisan l5-swagger:generate
```

### Option 2: Manual Update

Edit `storage/api-docs/api-docs.json` directly with updated endpoint definitions.

### Option 3: Using Custom Command

```bash
php artisan swagger:generate
```

---

## File Structure

```
app/
  ├── OpenAPI/
  │   ├── OpenAPIDoc.php          # Base configuration
  │   ├── IncidentEndpoints.php    # Incident endpoints
  │   ├── SyncEndpoints.php        # Sync + detailed docs
  │   └── OtherEndpoints.php       # Other modules
  │
  └── Http/Controllers/Api/
      ├── SwaggerController.php         # Serves UI & JSON
      └── V1/
          └── AuthControllerDocumented.php  # Example annotated

config/
  └── l5-swagger.php        # Configuration

routes/
  └── api.php               # Swagger UI routes added

resources/views/
  └── swagger.blade.php     # Swagger UI template

storage/api-docs/
  └── api-docs.json        # Generated OpenAPI spec

bootstrap/
  └── providers.php         # Service provider registered
```

---

## Testing the Documentation

### 1. **In Browser**

```
http://localhost/api/documentation
```

### 2. **Test Login Endpoint**

1. Expand **Auth** → **User login**
2. Click **"Try it out"**
3. Enter credentials:
    ```json
    {
        "email": "admin@example.com",
        "password": "password",
        "device_name": "Test Device"
    }
    ```
4. Click **"Execute"**
5. Copy the returned `token` value

### 3. **Test Protected Endpoint**

1. Click **"Authorize"** button
2. Paste the token
3. Click **"Authorize"**
4. Navigate to any protected endpoint and click **"Try it out"**
5. The token will be included automatically

### 4. **Test Sync Endpoint**

1. Use **Sync** → **Synchronize offline data**
2. Payload example:
    ```json
    {
        "device_id": "test-device-1",
        "last_synced_at": "2026-03-27T00:00:00Z",
        "conflict_strategy": "last_updated_wins",
        "data": {
            "incidents": [
                {
                    "temporary_id": "temp-1",
                    "title": "Test incident",
                    "status": "reported",
                    "classification": "high_risk",
                    "location": "Test Site",
                    "datetime": "2026-03-28T12:00:00Z",
                    "local_created_at": "2026-03-28T11:50:00Z"
                }
            ]
        }
    }
    ```

---

## Best Practices

### ✅ Documentation Standards

1. **Always include descriptions** of what endpoints do
2. **Document request parameters** with types and examples
3. **Show response schemas** with actual examples
4. **Group by tags** (Auth, Incidents, Training, etc.)
5. **Document error responses** (401, 403, 404, 422, 500)
6. **Security schemes** clearly marked on protected endpoints

### ✅ Sync Implementation

1. **Use temporary_id** for all offline-created records
2. **Always send local_created_at** with offline data
3. **Handle conflicts** in client code gracefully
4. **Map temp_id → server_id** after successful sync
5. **Update last_synced_at** for next sync cycle
6. **Test conflict scenarios** (concurrent edits)

### ✅ API Design

1. Response always has `success`, `message`, `data` structure
2. Paginated responses include `meta` with pagination info
3. Errors return appropriate HTTP status codes
4. Bearer token sent in `Authorization: Bearer {token}` header
5. All timestamps in ISO 8601 format (UTC)

---

## Troubleshooting

### Swagger UI not loading

1. Check that view `resources/views/swagger.blade.php` exists
2. Verify routes in `routes/api.php` include Swagger routes
3. Check browser console for JavaScript errors
4. Ensure `storage/api-docs/api-docs.json` exists and is valid JSON

### Token not persisting in Swagger UI

1. Browser must allow local storage
2. Token is saved to `localStorage['api_token']`
3. Check browser DevTools → Application → Local Storage

### Documentation not updating

1. Edit JSON directly: `storage/api-docs/api-docs.json`
2. Or regenerate from annotations if command works
3. Clear browser cache (Ctrl+Shift+Delete)

---

## Additional Resources

### Swagger/OpenAPI

- [OpenAPI 3.0 Specification](https://spec.openapis.org/oas/v3.0.3)
- [Swagger UI Documentation](https://swagger.io/tools/swagger-ui/)
- [APIs.guru - API Examples](https://apis.guru/)

### Laravel L5-Swagger

- [Package Repository](https://github.com/darkaonline/L5-Swagger)
- [Documentation](https://github.com/darkaonline/L5-Swagger)

### Offline Sync Patterns

- [Conflict Resolution Strategies](https://en.wikipedia.org/wiki/Operational_transformation)
- [Optimistic Concurrency Control](https://en.wikipedia.org/wiki/Optimistic_concurrency_control)

---

## Summary

✅ **Swagger/OpenAPI integration complete!**

Your API now has:

- 📚 Interactive Swagger UI documentation at `/api/documentation`
- 🔒 Bearer token authentication examples
- 📋 Complete endpoint specifications with examples
- 🔄 Detailed offline sync documentation
- 💾 OpenAPI JSON spec at `/api/documentation/json`

Developers can now explore, test, and integrate with your API directly from the browser!
