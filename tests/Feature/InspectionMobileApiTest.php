<?php

namespace Tests\Feature;

use App\Models\InspectionChecklist;
use App\Models\InspectionChecklistItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InspectionMobileApiTest extends TestCase
{
    use RefreshDatabase;

    private function mobileAuthHeaders(User $user): array
    {
        $plainPassword = 'password';
        $user->update(['password' => bcrypt($plainPassword)]);

        $login = $this->postJson(route('api.inspection.auth.login'), [
            'email' => $user->email,
            'password' => $plainPassword,
            'device_name' => 'test-device',
        ]);

        $login->assertCreated();
        $token = $login->json('data.token');

        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }

    public function test_mobile_api_can_fetch_active_checklists(): void
    {
        $checklist = InspectionChecklist::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'title' => 'Warehouse Daily Checklist',
            'version' => 1,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        InspectionChecklistItem::query()->create([
            'inspection_checklist_id' => $checklist->id,
            'offline_uuid' => (string) Str::uuid(),
            'label' => 'Emergency exit clear',
            'item_type' => 'boolean',
            'is_required' => true,
            'sort_order' => 0,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $user = User::factory()->create();
        $headers = $this->mobileAuthHeaders($user);

        $response = $this->withHeaders($headers)->getJson(route('api.inspection.checklists.index'));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $checklist->id);
        $response->assertJsonPath('data.0.items.0.label', 'Emergency exit clear');
    }

    public function test_mobile_api_can_start_update_and_submit_inspection_run(): void
    {
        $inspector = User::factory()->create();
        $headers = $this->mobileAuthHeaders($inspector);

        $checklist = InspectionChecklist::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'title' => 'Electrical Room Checklist',
            'version' => 1,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $itemA = InspectionChecklistItem::query()->create([
            'inspection_checklist_id' => $checklist->id,
            'offline_uuid' => (string) Str::uuid(),
            'label' => 'Main breaker condition',
            'item_type' => 'choice',
            'options' => ['Good', 'Needs repair'],
            'is_required' => true,
            'sort_order' => 0,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $itemB = InspectionChecklistItem::query()->create([
            'inspection_checklist_id' => $checklist->id,
            'offline_uuid' => (string) Str::uuid(),
            'label' => 'Area notes',
            'item_type' => 'text',
            'is_required' => false,
            'sort_order' => 1,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $createResponse = $this->withHeaders($headers)->postJson(route('api.inspection.runs.store'), [
            'inspection_checklist_id' => $checklist->id,
            'location' => 'Electrical Room A',
            'device_identifier' => 'mobile-01',
            'offline_reference' => 'offline-ref-1',
        ]);

        $createResponse->assertCreated();

        $inspectionId = $createResponse->json('data.id');
        $responses = $createResponse->json('data.responses');

        $this->assertCount(2, $responses);

        $responseA = collect($responses)->firstWhere('inspection_checklist_item_id', $itemA->id);
        $responseB = collect($responses)->firstWhere('inspection_checklist_item_id', $itemB->id);

        $updateResponse = $this->withHeaders($headers)->putJson(route('api.inspection.runs.responses.update', $inspectionId), [
            'responses' => [
                [
                    'response_id' => $responseA['id'],
                    'response_value' => 'Good',
                    'is_non_compliant' => false,
                ],
                [
                    'response_id' => $responseB['id'],
                    'response_value' => 'All clear.',
                    'comment' => 'No visible damage.',
                    'is_non_compliant' => false,
                ],
            ],
            'mark_as_completed' => true,
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.status', 'completed');

        $submitResponse = $this->withHeaders($headers)->postJson(route('api.inspection.runs.submit', $inspectionId));

        $submitResponse->assertOk();
        $submitResponse->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('inspections', [
            'id' => $inspectionId,
            'status' => 'submitted',
            'inspector_id' => $inspector->id,
        ]);

        $this->withHeaders($headers)
            ->postJson(route('api.inspection.auth.logout'))
            ->assertOk();
    }

    public function test_mobile_api_blocks_access_to_other_users_inspection_run(): void
    {
        $owner = User::factory()->create();
        $ownerHeaders = $this->mobileAuthHeaders($owner);
        $otherUser = User::factory()->create();
        $otherHeaders = $this->mobileAuthHeaders($otherUser);

        $checklist = InspectionChecklist::query()->create([
            'offline_uuid' => (string) Str::uuid(),
            'title' => 'Restricted Checklist',
            'version' => 1,
            'is_active' => true,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        InspectionChecklistItem::query()->create([
            'inspection_checklist_id' => $checklist->id,
            'offline_uuid' => (string) Str::uuid(),
            'label' => 'Owner only check',
            'item_type' => 'boolean',
            'is_required' => true,
            'sort_order' => 0,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);

        $createResponse = $this->withHeaders($ownerHeaders)->postJson(route('api.inspection.runs.store'), [
            'inspection_checklist_id' => $checklist->id,
            'location' => 'Restricted Area',
            'device_identifier' => 'owner-device',
        ]);

        $createResponse->assertCreated();
        $inspectionId = $createResponse->json('data.id');

        $this->withHeaders($otherHeaders)
            ->getJson(route('api.inspection.runs.show', $inspectionId))
            ->assertStatus(403);

        $this->withHeaders($otherHeaders)
            ->postJson(route('api.inspection.runs.submit', $inspectionId))
            ->assertStatus(403);
    }
}
