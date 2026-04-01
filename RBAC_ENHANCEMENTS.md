# RBAC Enhancements Guide

This document describes the advanced Role-Based Access Control (RBAC) enhancements implemented in your Laravel application.

---

## Overview

Four major enhancements have been added to provide production-grade RBAC security:

1. **Audit Logging** — Track all authorization checks and security events
2. **Permission Matrix Report** — Generate compliance reports of role-permission mappings
3. **Request Signing** — Cryptographically sign sensitive API requests for non-repudiation
4. **Resource-Level Scoping** — Filter query results based on user role/ownership

---

## 1. Audit Logging

### What It Does

The `AuditService` logs all authorization checks, denials, and escalations to the `audit_logs` table for compliance and forensic analysis.

### Usage in Controllers

```php
use App\Services\AuditService;
use App\Traits\LogsRbacActions;

class IncidentController extends Controller
{
    use LogsRbacActions;

    public function show(Incident $incident)
    {
        $this->authorize('view', $incident);

        // Log allowed access
        $this->auditLog(auth()->user(), 'view_incident', 'Incident', 'allowed', $incident);

        return view('incidents.show', compact('incident'));
    }

    public function destroy(Incident $incident)
    {
        $this->authorize('delete', $incident);

        // Log escalated action (Admin-only delete)
        $this->auditLogEscalated(auth()->user(), 'delete_incident', 'Incident', $incident, [
            'escalation_context' => 'Admin hard-delete of production incident',
        ]);

        $incident->delete();
    }
}
```

### Querying Audit Logs

```php
use App\Services\AuditService;

// Get all actions by a specific user
$userActivity = AuditService::queryUserActivity($user, 'Incident', 'view_incident')->get();

// Get security events (denied/attempted)
$securityEvents = AuditService::querySecurityEvents()->get();

// Custom queries
AuditLog::where('module', 'Incident')
    ->whereJsonContains('metadata->result', 'denied')
    ->orderByDesc('created_at')
    ->get();
```

### Audit Log Schema

```php
// Fields in audit_logs table
'user_id'          => id of the user performing action
'action'           => permission/ability checked (e.g., 'view_incident')
'module'           => feature module (e.g., 'Incident', 'Training')
'auditable_type'   => model class (e.g., App\Models\Incident)
'auditable_id'     => model id if applicable
'metadata' => [
    'result'       => 'allowed', 'denied', 'escalated', 'attempted'
    'ip_address'   => request IP
    'user_agent'   => browser/client string
    'timestamp'    => ISO8601 timestamp
]
```

---

## 2. Permission Matrix Report

### Generate a Permission-Role Matrix

```bash
# Display as table (default)
php artisan rbac:matrix

# Export to CSV
php artisan rbac:matrix --format=csv --export=permissions.csv

# Export to JSON
php artisan rbac:matrix --format=json --export=permissions.json

# Export to text file
php artisan rbac:matrix --format=table --export=permissions.txt
```

### Example Output

```
Permission                  | Admin | Manager | Worker | Safety Officer | ...
view_incident              | ✓     | ✓       | ✓      | ✓              | ...
create_incident            | ✓     | ✓       |        | ✓              | ...
approve_final              | ✓     |         |        | ✓              | ...
```

This helps:

- Document compliance matrices for audits
- Verify role-permission alignment
- Identify permission drift
- Onboard new team members

---

## 3. Request Signing (Non-Repudiation)

### What It Does

HMAC-SHA256 signing ensures sensitive API requests are:

- **Authentic** — Signed by a client with the shared secret
- **Tamper-proof** — Request body cannot be modified without recomputing signature
- **Non-repudiable** — Client cannot deny sending the request

### Setup

#### 1. Generate a Shared Secret

```bash
# In your .env file
API_SIGNATURE_SECRET="your-very-long-random-base64-string"
```

#### 2. Protect Sensitive Routes

```php
// routes/api.php
Route::post('/api/incidents/{incident}/approve-final', ApproveIncidentController::class)
    ->middleware('auth:sanctum', 'verify-signature:api_signature_secret');
```

#### 3. Client Usage (JavaScript)

```javascript
// Compute HMAC-SHA256 signature
async function signRequest(payload, secret) {
    const encoder = new TextEncoder();
    const data = encoder.encode(JSON.stringify(payload));
    const key = await crypto.subtle.importKey(
        "raw",
        encoder.encode(secret),
        { name: "HMAC", hash: "SHA-256" },
        false,
        ["sign"],
    );
    const signature = await crypto.subtle.sign("HMAC", key, data);
    const base64Sig = btoa(String.fromCharCode(...new Uint8Array(signature)));
    return "sha256=" + base64Sig;
}

// Example: POST to protected endpoint
fetch("/api/incidents/123/approve-final", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer " + token,
        "X-Signature": await signRequest(payload, clientSecret),
    },
    body: JSON.stringify(payload),
});
```

#### 4. Client Usage (PHP)

```php
$payload = json_encode(['status' => 'approved']);
$secret = config('services.api_signature_secret');
$signature = 'sha256=' . base64_encode(
    hash_hmac('sha256', $payload, $secret, true)
);

$response = Http::withHeaders([
    'X-Signature' => $signature,
    'Authorization' => 'Bearer ' . $token,
])->post('/api/incidents/123/approve-final', $payload);
```

#### 5. Client Usage (cURL)

```bash
PAYLOAD='{"status":"approved"}'
SECRET="your-shared-secret"
SIGNATURE="sha256=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" -binary | base64)"

curl -X POST https://api.example.com/api/incidents/123/approve-final \
  -H "X-Signature: $SIGNATURE" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD"
```

### Audit Trail

All signed requests are logged in `audit_logs` with:

- `ip_address` — Source of request
- `user_agent` — Client software
- Full decision trail for non-repudiation

---

## 4. Resource-Level Scoping

### What It Does

Database queries are automatically scoped so users only see records they have permission to access.

### Supported Models

- **Incident** — Scoped by role (Admin sees all, Worker sees own, etc.)
- **Training** — Supervisory roles see all; Workers see assigned only
- **Worker** — Supervisory roles see all; Workers see self only

### Usage in Policies

```php
use App\Policies\IncidentPolicy;

class IncidentPolicy
{
    public function view(User $user, Incident $incident): bool
    {
        // Check permission first
        if (! $user->hasPermissionTo('view_incident')) {
            return false;
        }

        // Then check resource-level access
        return $incident->isAccessibleTo($user);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_incident');
    }
}
```

### Usage in Controllers

```php
class IncidentController extends Controller
{
    public function index()
    {
        // Automatically filtered by resource scoping
        $incidents = Incident::accessibleTo(auth()->user())->get();

        return view('incidents.index', compact('incidents'));
    }
}
```

### Custom Scoping Rules

To add scoping to your own models:

```php
use App\Traits\HasResourceScoping;
use Illuminate\Database\Eloquent\Builder;

class MyModel extends Model
{
    use HasResourceScoping;

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('Admin')) {
            return $query; // Admins see all
        }

        if ($user->hasRole('Manager')) {
            return $query->where('team_id', $user->team_id); // Managers see team only
        }

        return $query->where('owner_id', $user->id); // Others see own only
    }
}
```

### Scoping Rules by Model

#### Incident

| Role                    | Access                                 |
| ----------------------- | -------------------------------------- |
| Admin                   | All incidents                          |
| HOD HSSE, APSB PD, MRTS | All incidents (workflow processing)    |
| Manager, Safety Officer | Incidents they reported OR investigate |
| Worker, Supervisor      | Own reported incidents only            |

#### Training

| Role                                                      | Access                  |
| --------------------------------------------------------- | ----------------------- |
| Admin                                                     | All trainings           |
| Manager, Safety Officer, HOD HSSE, APSB PD, MRTS, Auditor | All trainings           |
| Worker                                                    | Assigned trainings only |

#### Worker

| Role                       | Access      |
| -------------------------- | ----------- |
| Admin, Manager, Supervisor | All workers |
| Worker                     | Self only   |

---

## Testing the Enhancements

Run the comprehensive test suite:

```bash
# Test all RBAC enhancements
php artisan test tests/Feature/RbacEnhancementsTest.php

# Full regression suite
php artisan test tests/Feature/ApiDashboardFeatureTest.php \
  tests/Feature/SidebarPermissionVisibilityTest.php \
  tests/Feature/AdminRouteRbacTest.php \
  tests/Feature/ApiResourceAuthorizationTest.php
```

---

## Best Practices

### 1. Audit Logging

✅ **DO**: Log sensitive actions and all denials

```php
$this->auditLog($user, 'approve_incident', 'Incident', 'allowed', $incident);
```

❌ **DON'T**: Log every page view

```php
// Too noisy
$this->auditLog($user, 'view_dashboard', 'Dashboard');
```

### 2. Request Signing

✅ **DO**: Sign financial, approval, and deletion endpoints

```php
->middleware('verify-signature:api_signature_secret')
```

❌ **DON'T**: Sign read-only GET requests

```php
// GET /api/incidents — no signature needed
```

### 3. Resource Scoping

✅ **DO**: Use scoping in policies

```php
return $incident->isAccessibleTo($user);
```

❌ **DON'T**: Hardcode user IDs in queries

```php
// Don't do this
$incidents = Incident::where('user_id', $user->id)->get();
```

### 4. Permission Matrix

✅ **DO**: Run monthly compliance checks

```bash
php artisan rbac:matrix --export=compliance-$(date +%Y-%m-%d).csv
```

❌ **DON'T**: Assume roles have the right permissions

```bash
# Verify instead of guessing
php artisan rbac:matrix
```

---

## Troubleshooting

### Audit logs not appearing

Check that the `audit_logs` table exists:

```bash
php artisan migrate
```

Verify the `AuditService` is called in your controller:

```php
use App\Services\AuditService;
AuditService::log($user, 'action', 'Module');
```

### Request signature validation fails

Ensure:

1. Client and server use the same secret (from `API_SIGNATURE_SECRET` env)
2. Client signs the **exact request body** (no formatting difference)
3. Middleware is applied to the correct routes

Debug with:

```php
// In middleware
Log::debug('Signature received', ['signature' => $signature]);
Log::debug('Payload', ['payload' => $request->getContent()]);
Log::debug('Expected', ['expected' => $expectedSignature]);
```

### Resource scoping returning no results

Check that the user's role is in the scoping rules:

```php
// View scoping rules
dd(Incident::accessibleTo($user)->toSql());
```

Verify the user has the necessary role/permission:

```php
$user->roles()->with('permissions')->get();
```

---

## Integration Examples

### Complete Workflow: Incident Approval

```php
class IncidentApprovalController extends Controller
{
    use LogsRbacActions;

    public function approveFinal(IncidentApprovalRequest $request, Incident $incident)
    {
        // 1. Authorization check (calls policy)
        $this->authorize('approve_final', $incident);

        // 2. Resource scoping (policy uses isAccessibleTo)
        [incident->isAccessibleTo(auth()->user())]

        // 3. Audit log the approval
        $this->auditLog(
            auth()->user(),
            'approve_final',
            'Incident',
            'allowed',
            $incident,
            ['reason' => $request->reason]
        );

        // 4. Update incident
        $incident->update([
            'status' => 'closed',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // 5. Log completion
        AuditService::log(
            auth()->user(),
            'approve_final',
            'Incident',
            'allowed',
            $incident,
            ['context' => 'Approval completed']
        );

        return redirect()->route('incidents.show', $incident)
            ->with('success', 'Incident approved and closed');
    }
}
```

With route:

```php
Route::post('/api/incidents/{incident}/approve-final', [IncidentApprovalController::class, 'approveFinal'])
    ->middleware('auth:sanctum', 'verify-signature:api_signature_secret')
    ->name('api.incidents.approve-final');
```

---

## Migration Guide

If you're adding these features to an existing project:

---

## Seeding Strategy (Baseline vs Demo)

The seeders are split into two categories to avoid accidental demo data in production:

1. **System baseline data (required by system)**
2. **Demo sample data (for demo/testing only)**

### Seeder Classes

- `SystemBaselineSeeder`: required baseline RBAC and core setup data
- `DemoSampleDataSeeder`: sample/demo records (users, incidents, training, etc.)
- `DatabaseSeeder`: orchestrates both with production safety rules

### Commands

```bash
# Baseline only
php artisan db:seed --class=SystemBaselineSeeder

# Demo/sample only
php artisan db:seed --class=DemoSampleDataSeeder

# Default orchestration
php artisan db:seed
```

### Production Safety Behavior

- In non-production environments, `DatabaseSeeder` runs baseline + demo seeders.
- In production, `DatabaseSeeder` runs baseline only.
- `DemoSampleDataSeeder` is blocked in production unless explicitly allowed.

To explicitly allow demo seeding in production (for controlled scenarios only):

```env
ALLOW_DEMO_SEEDING=true
```

Then run:

```bash
php artisan db:seed --class=DemoSampleDataSeeder
```

### Recommended CI/CD Pattern

- `production`: run `SystemBaselineSeeder` only
- `staging/qa`: run baseline + demo as needed
- `local/dev`: run full `DatabaseSeeder`

### Step 1: Run Migrations

```bash
php artisan migrate
```

### Step 2: Add Traits to Models

```php
use App\Traits\HasResourceScoping;

class Incident extends Model
{
    use HasResourceScoping;
}
```

### Step 3: Register Middleware

Already done in `bootstrap/app.php`:

```php
'verify-signature' => VerifyRequestSignature::class,
```

### Step 4: Configure .env

```env
API_SIGNATURE_SECRET=your-base64-encoded-secret
```

### Step 5: Test

```bash
php artisan test tests/Feature/RbacEnhancementsTest.php
```

---

## API Documentation

See `storage/api-docs/api-docs.json` for Swagger documentation on all endpoints including RBAC controls.

---

## Further Reading

- [Laravel Policies](https://laravel.com/docs/authorization#policies)
- [Laravel Gates](https://laravel.com/docs/authorization#gates)
- [HMAC-SHA256](https://en.wikipedia.org/wiki/HMAC)
- [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)
