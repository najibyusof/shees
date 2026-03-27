# SHEES API - Quick Start Guide

## 🚀 Access Documentation

**URL:** `http://localhost/api/documentation`

![Swagger UI Available](✓)

---

## 📝 Quick Commands

### Clear Cache (if needed)

```bash
php artisan cache:clear
php artisan config:cache
```

### View Routes

```bash
php artisan route:list | findstr documentation
```

### Test with cURL

```bash
# Login
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "cURL Test"
  }'

# Get current user (replace TOKEN with actual token)
curl -X GET http://localhost/api/auth/user \
  -H "Authorization: Bearer TOKEN"
```

---

## 🎯 Endpoint Summary

| Method     | Path                  | Description                   |
| ---------- | --------------------- | ----------------------------- |
| **POST**   | `/api/auth/login`     | Login (returns Bearer token)  |
| **POST**   | `/api/auth/logout`    | Logout                        |
| **GET**    | `/api/auth/user`      | Get current user              |
| **GET**    | `/api/incidents`      | List incidents                |
| **POST**   | `/api/incidents`      | Create incident               |
| **GET**    | `/api/incidents/{id}` | Get incident                  |
| **PUT**    | `/api/incidents/{id}` | Update incident               |
| **DELETE** | `/api/incidents/{id}` | Delete incident               |
| **POST**   | `/api/sync`           | **Offline sync** (important!) |
| **GET**    | `/api/training`       | List training                 |
| **POST**   | `/api/training`       | Create training               |
| **GET**    | `/api/inspections`    | List inspections              |
| **POST**   | `/api/inspections`    | Create inspection             |
| **GET**    | `/api/workers`        | List workers                  |
| **GET**    | `/api/attendance`     | List attendance               |
| **POST**   | `/api/attendance`     | Record attendance             |

---

## 🔑 Authentication

### In Swagger UI:

1. Click **Authorize** button
2. Login at `/api/auth/login` endpoint
3. Copy the `token` from response
4. Paste into Authorization dialog
5. All protected endpoints now work

### Bearer Token Usage:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## 📦 Response Format

### Success

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "meta": { ... }
}
```

### Error

```json
{
    "success": false,
    "message": "Error message",
    "errors": { "field": ["details"] }
}
```

---

## 🔄 Offline Sync Pattern

**Endpoint:** `POST /api/sync`

**Purpose:** Synchronize offline data with conflict resolution

```json
{
    "device_id": "device-unique-id",
    "last_synced_at": "2026-03-27T08:00:00Z",
    "conflict_strategy": "last_updated_wins",
    "data": {
        "incidents": [
            {
                "temporary_id": "uuid-for-offline-tracking",
                "title": "Incident title",
                "status": "reported",
                "datetime": "2026-03-28T14:30:00Z",
                "local_created_at": "2026-03-28T14:20:00Z"
            }
        ]
    }
}
```

**Response includes:**

- `synced_records`: Newly synced items with server IDs
- `server_updates`: All changes from server since last_synced_at
- `conflicts`: Any conflicts detected

---

## 📚 Files Created/Modified

### New Files

- `config/l5-swagger.php` - L5Swagger configuration
- `app/OpenAPI/OpenAPIDoc.php` - Base OpenAPI config
- `app/OpenAPI/IncidentEndpoints.php` - Incident documentation
- `app/OpenAPI/SyncEndpoints.php` - Sync documentation + explanation
- `app/OpenAPI/OtherEndpoints.php` - Other modules
- `app/Console/Commands/GenerateSwaggerDocs.php` - Custom generator
- `app/Http/Controllers/Api/SwaggerController.php` - UI controller
- `app/Http/Controllers/Api/V1/AuthControllerDocumented.php` - Annotated example
- `resources/views/swagger.blade.php` - Swagger UI template
- `storage/api-docs/api-docs.json` - Generated OpenAPI spec
- `SWAGGER_SETUP.md` - Comprehensive setup guide

### Modified Files

- `bootstrap/providers.php` - Added L5SwaggerServiceProvider
- `routes/api.php` - Added Swagger UI routes

---

## ✅ Verification Checklist

- [x] Package installed: `darkaonline/l5-swagger`
- [x] Service provider registered in `bootstrap/providers.php`
- [x] Configuration created in `config/l5-swagger.php`
- [x] OpenAPI annotations in `app/OpenAPI/`
- [x] Swagger UI controller created
- [x] Swagger blade template created
- [x] Routes added to `routes/api.php`
- [x] API docs JSON generated: `storage/api-docs/api-docs.json`
- [x] Documentation accessible at `/api/documentation`

---

## 🔗 URLs

| Purpose            | URL                                       |
| ------------------ | ----------------------------------------- |
| **Swagger UI**     | `http://localhost/api/documentation`      |
| **OpenAPI JSON**   | `http://localhost/api/documentation/json` |
| **Login Endpoint** | `POST http://localhost/api/auth/login`    |
| **Incidents**      | `http://localhost/api/incidents`          |

---

## 📞 Support

For detailed documentation, see: **`SWAGGER_SETUP.md`**

For API endpoint details, check Swagger UI at: **`/api/documentation`**

---

**Status:** ✅ Complete and Ready to Use!
