<?php

namespace App\Services;

use App\Models\InspectionChecklist;
use App\Models\InspectionChecklistItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InspectionChecklistService
{
    public function create(array $data, User $actor): InspectionChecklist
    {
        return DB::transaction(function () use ($data, $actor) {
            $checklist = InspectionChecklist::query()->create([
                'offline_uuid' => (string) Str::uuid(),
                'code' => $data['code'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'title_translations' => $this->cleanTranslations($data['title_translations'] ?? []),
                'description_translations' => $this->cleanTranslations($data['description_translations'] ?? []),
                'version' => 1,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'created_by' => $actor->id,
            ]);

            $this->replaceItems($checklist, $data['items'] ?? []);

            return $checklist->load('items');
        });
    }

    public function update(InspectionChecklist $checklist, array $data): InspectionChecklist
    {
        return DB::transaction(function () use ($checklist, $data) {
            $checklist->update([
                'code' => $data['code'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'title_translations' => $this->cleanTranslations($data['title_translations'] ?? []),
                'description_translations' => $this->cleanTranslations($data['description_translations'] ?? []),
                'version' => $checklist->version + 1,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'sync_status' => 'pending_sync',
            ]);

            $this->replaceItems($checklist, $data['items'] ?? []);

            return $checklist->load('items');
        });
    }

    private function replaceItems(InspectionChecklist $checklist, array $items): void
    {
        $checklist->items()->delete();

        foreach ($items as $index => $item) {
            $options = array_filter(array_map('trim', explode(',', (string) ($item['options'] ?? ''))));

            InspectionChecklistItem::query()->create([
                'inspection_checklist_id' => $checklist->id,
                'offline_uuid' => (string) Str::uuid(),
                'label' => $item['label'],
                'label_translations' => $this->cleanTranslations($item['label_translations'] ?? []),
                'item_type' => $item['item_type'],
                'options' => in_array($item['item_type'], ['choice'], true) ? array_values($options) : null,
                'is_required' => (bool) ($item['is_required'] ?? false),
                'sort_order' => $item['sort_order'] ?? $index,
                'sync_status' => $checklist->sync_status,
                'last_synced_at' => $checklist->last_synced_at,
            ]);
        }
    }

    private function cleanTranslations(array $translations): array
    {
        return array_filter($translations, fn ($value) => filled($value));
    }
}
