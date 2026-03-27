<?php

namespace Database\Seeders;

use App\Models\Inspection;
use App\Models\InspectionChecklist;
use App\Models\InspectionChecklistItem;
use App\Models\InspectionResponse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InspectionSeeder extends Seeder
{
    public function run(): void
    {
        $supervisors = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Supervisor'))
            ->get();

        if ($supervisors->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 10; $i++) {
            $supervisor = $supervisors->random();

            $checklist = InspectionChecklist::query()->create([
                'offline_uuid' => (string) Str::uuid(),
                'code' => 'CHK-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'title' => 'Supervisor Checklist '.($i + 1),
                'description' => fake()->sentence(10),
                'title_translations' => null,
                'description_translations' => null,
                'version' => 1,
                'is_active' => true,
                'sync_status' => 'synced',
                'sync_batch_uuid' => null,
                'last_synced_at' => now(),
                'created_by' => $supervisor->id,
            ]);

            $itemCount = random_int(5, 10);
            $items = collect();
            for ($itemNo = 1; $itemNo <= $itemCount; $itemNo++) {
                $items->push(InspectionChecklistItem::query()->create([
                    'inspection_checklist_id' => $checklist->id,
                    'offline_uuid' => (string) Str::uuid(),
                    'label' => fake()->randomElement([
                        'Emergency exit signage visible',
                        'PPE available at station',
                        'Housekeeping condition acceptable',
                        'Machine guarding intact',
                        'Chemical labels legible',
                    ]),
                    'label_translations' => null,
                    'item_type' => 'choice',
                    'options' => ['passed', 'failed', 'na'],
                    'is_required' => true,
                    'sort_order' => $itemNo,
                    'sync_status' => 'synced',
                    'sync_batch_uuid' => null,
                    'last_synced_at' => now(),
                ]));
            }

            $status = random_int(1, 100) <= 70 ? 'completed' : 'submitted';
            $performedAt = now()->subDays(random_int(1, 40));

            $inspection = Inspection::query()->create([
                'offline_uuid' => (string) Str::uuid(),
                'inspection_checklist_id' => $checklist->id,
                'inspector_id' => $supervisor->id,
                'status' => $status,
                'location' => fake()->randomElement(['Production Line', 'Warehouse', 'Compressor Room', 'Dispatch Area']),
                'performed_at' => $performedAt,
                'submitted_at' => $status === 'submitted' ? (clone $performedAt)->addHours(2) : null,
                'notes' => fake()->sentence(12),
                'device_identifier' => 'sup-device-'.fake()->numerify('##'),
                'offline_reference' => 'INSP-'.fake()->numerify('######'),
                'sync_status' => 'synced',
                'sync_batch_uuid' => null,
                'last_synced_at' => now(),
            ]);

            foreach ($items as $item) {
                $failed = random_int(1, 100) <= 35;

                InspectionResponse::query()->create([
                    'inspection_id' => $inspection->id,
                    'inspection_checklist_item_id' => $item->id,
                    'offline_uuid' => (string) Str::uuid(),
                    'response_value' => $failed ? 'failed' : 'passed',
                    'response_meta' => ['seeded' => true],
                    'is_non_compliant' => $failed,
                    'comment' => $failed
                        ? fake()->sentence(10)
                        : fake()->optional()->sentence(6),
                    'sync_status' => 'synced',
                    'sync_batch_uuid' => null,
                    'last_synced_at' => now(),
                ]);
            }
        }
    }
}
