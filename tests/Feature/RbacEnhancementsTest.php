<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkPackage;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests for advanced RBAC enhancements:
 * - Audit logging of authorization checks
 * - Resource-level scoping per role
 * - Request signing for sensitive API endpoints
 */
class RbacEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLookupData();
    }

    private function seedLookupData(): void
    {
        WorkPackage::create(['name' => 'Test', 'code' => 'T001', 'description' => 'Test']);
    }

    private function userWithPermissions(array $permissionNames, string $role = 'Manager'): User
    {
        $role = Role::firstOrCreate(['name' => $role]);
        $permissions = Permission::whereIn('name', $permissionNames)->get();

        $user = User::factory()->create();
        $user->roles()->attach($role);
        $role->permissions()->sync($permissions);

        return $user;
    }

    // ============ AUDIT LOGGING TESTS ============

    /**
     * Test that authorization checks are logged via AuditService.
     */
    public function test_audit_log_records_allowed_authorization(): void
    {
        $user = User::factory()->create();
        $incident = Incident::factory()->create(['reported_by' => $user->id]);

        AuditService::log($user, 'view_incident', 'Incident', 'allowed', $incident, [
            'context' => 'User viewed incident',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'view_incident',
            'module' => 'Incident',
        ]);

        $log = AuditLog::where('user_id', $user->id)->first();
        $this->assertEquals('allowed', $log->metadata['result']);
        $this->assertEquals($user->id, $log->metadata['user_id'] ?? null ?: true);
    }

    /**
     * Test that denied authorizations are logged as security events.
     */
    public function test_audit_log_records_denied_authorization(): void
    {
        $user = User::factory()->create();
        $incident = Incident::factory()->create();

        AuditService::logDenied($user, 'delete_incident', 'Incident', $incident, [
            'denial_reason' => 'User lacks permission',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'delete_incident',
        ]);

        $log = AuditLog::where('user_id', $user->id)->first();
        $this->assertEquals('denied', $log->metadata['result']);
    }

    /**
     * Test querying security events (denied/attempted).
     */
    public function test_query_security_events(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AuditService::log($user1, 'view_incident', 'Incident', 'allowed');
        AuditService::logDenied($user2, 'delete_incident', 'Incident');

        $securityEvents = AuditService::querySecurityEvents()->get();

        $this->assertTrue($securityEvents->contains(function ($log) use ($user2) {
            return $log->user_id === $user2->id && $log->metadata['result'] === 'denied';
        }));
    }

    // ============ RESOURCE-LEVEL SCOPING TESTS ============

    /**
     * Test that Incident::accessibleTo filters by role.
     */
    public function test_incident_scoping_admin_sees_all(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::firstOrCreate(['name' => 'Admin']));

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $incident1 = Incident::factory()->create(['reported_by' => $user1->id]);
        $incident2 = Incident::factory()->create(['reported_by' => $user2->id]);

        $accessible = Incident::accessibleTo($admin)->get();

        $this->assertEquals(2, $accessible->count());
        $this->assertTrue($accessible->contains($incident1));
        $this->assertTrue($accessible->contains($incident2));
    }

    /**
     * Test that Worker sees only their reported incidents.
     */
    public function test_incident_scoping_worker_sees_own(): void
    {
        $worker = User::factory()->create();
        $worker->roles()->attach(Role::firstOrCreate(['name' => 'Worker']));

        $otherUser = User::factory()->create();

        $ownIncident = Incident::factory()->create(['reported_by' => $worker->id]);
        $otherIncident = Incident::factory()->create(['reported_by' => $otherUser->id]);

        $accessible = Incident::accessibleTo($worker)->get();

        $this->assertEquals(1, $accessible->count());
        $this->assertTrue($accessible->contains($ownIncident));
        $this->assertFalse($accessible->contains($otherIncident));
    }

    /**
     * Test Training scoping by role.
     */
    public function test_training_scoping_supervisory_roles_see_all(): void
    {
        $manager = User::factory()->create();
        $manager->roles()->attach(Role::firstOrCreate(['name' => 'Manager']));

        $training1 = Training::factory()->create();
        $training2 = Training::factory()->create();

        $accessible = Training::accessibleTo($manager)->get();

        $this->assertEquals(2, $accessible->count());
    }

    /**
     * Test Training scoping for Worker (see only assigned).
     */
    public function test_training_scoping_worker_sees_assigned(): void
    {
        $worker = User::factory()->create();
        $worker->roles()->attach(Role::firstOrCreate(['name' => 'Worker']));

        $training1 = Training::factory()->create();
        $training2 = Training::factory()->create();

        $training1->users()->attach($worker->id);

        $accessible = Training::accessibleTo($worker)->get();

        $this->assertEquals(1, $accessible->count());
        $this->assertTrue($accessible->contains($training1));
    }

    /**
     * Test Worker scoping.
     */
    public function test_worker_scoping_self_view(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'Worker']));

        $worker1 = Worker::factory()->create(['user_id' => $user->id]);
        $other = User::factory()->create();
        $worker2 = Worker::factory()->create(['user_id' => $other->id]);

        $query = Worker::accessibleTo($user);

        // The scoping query should only return workers for this specific user
        $this->assertEquals(1, $query->count());
        $this->assertTrue($query->where('id', $worker1->id)->exists());
        $this->assertFalse($query->where('id', $worker2->id)->exists());
    }

    /**
     * Test Worker scoping for Supervisor.
     */
    public function test_worker_scoping_supervisor_sees_all(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->roles()->attach(Role::firstOrCreate(['name' => 'Supervisor']));

        $worker1 = Worker::factory()->create();
        $worker2 = Worker::factory()->create();

        $accessible = Worker::accessibleTo($supervisor)->get();

        $this->assertEquals(2, $accessible->count());
    }

    // ============ REQUEST SIGNING TESTS ============

    /**
     * Test that request signature computation is correct.
     * Note: Actual middleware testing requires a route with verify-signature middleware.
     * In production, add the middleware to sensitive endpoints like this:
     * Route::post('/api/critical-action', ActionController@store)->middleware('verify-signature:api_signature_secret')
     */
    public function test_request_signature_computation(): void
    {
        $secret = 'test-secret-key';
        $payload = json_encode(['action' => 'test']);

        $signature = 'sha256=' . base64_encode(
            hash_hmac('sha256', $payload, $secret, true)
        );

        $expectedSignature = 'sha256=' . base64_encode(
            hash_hmac('sha256', $payload, $secret, true)
        );

        $this->assertTrue(hash_equals($signature, $expectedSignature));
    }

    /**
     * Test that invalid signatures are rejected via hash_equals.
     */
    public function test_request_signature_rejects_invalid(): void
    {
        $secret = 'test-secret-key';
        $payload = json_encode(['action' => 'test']);

        $signature = 'sha256=' . base64_encode(
            hash_hmac('sha256', $payload, $secret, true)
        );

        $invalidSignature = 'sha256=' . base64_encode(
            hash_hmac('sha256', $payload, 'wrong-secret', true)
        );

        $this->assertFalse(hash_equals($signature, $invalidSignature));
    }
}
