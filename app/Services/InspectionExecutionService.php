<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\InspectionChecklist;
use App\Models\InspectionResponse;
use App\Models\InspectionResponseImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InspectionExecutionService
{
    public function __construct(private readonly InspectionSyncService $syncService) {}

    public function start(array $data, User $inspector): Inspection
    {
        return DB::transaction(function () use ($data, $inspector) {
            $checklist = InspectionChecklist::query()->with('items')->findOrFail($data['inspection_checklist_id']);

            $inspection = Inspection::query()->create([
                'offline_uuid' => (string) Str::uuid(),
                'inspection_checklist_id' => $checklist->id,
                'inspector_id' => $inspector->id,
                'status' => $data['status'] ?? 'draft',
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'device_identifier' => $data['device_identifier'] ?? null,
                'offline_reference' => $data['offline_reference'] ?? null,
                'sync_status' => 'pending_sync',
            ]);

            foreach ($checklist->items as $item) {
                InspectionResponse::query()->create([
                    'inspection_id' => $inspection->id,
                    'inspection_checklist_item_id' => $item->id,
                    'offline_uuid' => (string) Str::uuid(),
                    'sync_status' => 'pending_sync',
                ]);
            }

            $inspection = $inspection->load(['checklist.items', 'responses.checklistItem']);
            $this->syncService->enqueueDownloadForInspection($inspection, 'create');

            return $inspection;
        });
    }

    public function updateResponses(Inspection $inspection, array $responses, bool $markAsCompleted = false): Inspection
    {
        return DB::transaction(function () use ($inspection, $responses, $markAsCompleted) {
            foreach ($responses as $responseId => $responseData) {
                $response = InspectionResponse::query()
                    ->where('inspection_id', $inspection->id)
                    ->find($responseId);

                if (! $response) {
                    continue;
                }

                $response->update([
                    'response_value' => $responseData['response_value'] ?? null,
                    'comment' => $responseData['comment'] ?? null,
                    'is_non_compliant' => (bool) ($responseData['is_non_compliant'] ?? false),
                    'sync_status' => 'pending_sync',
                ]);
            }

            if ($markAsCompleted) {
                $inspection->update([
                    'status' => 'completed',
                    'performed_at' => $inspection->performed_at ?? now(),
                    'sync_status' => 'pending_sync',
                ]);
            }

            $inspection = $inspection->fresh(['checklist.items', 'responses.images', 'responses.checklistItem']);
            $this->syncService->enqueueDownloadForInspection($inspection, 'update');

            return $inspection;
        });
    }

    public function uploadImage(
        Inspection $inspection,
        InspectionResponse $response,
        UploadedFile $file,
        User $user,
        ?string $capturedAt = null
    ): InspectionResponseImage {
        if ($response->inspection_id !== $inspection->id) {
            throw ValidationException::withMessages([
                'image' => 'The selected response does not belong to this inspection.',
            ]);
        }

        $path = $file->store('inspection-images', 'public');

        $image = InspectionResponseImage::query()->create([
            'inspection_response_id' => $response->id,
            'uploaded_by' => $user->id,
            'offline_uuid' => (string) Str::uuid(),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?? 0,
            'captured_at' => $capturedAt,
            'sync_status' => 'pending_sync',
        ]);

        $response->update(['sync_status' => 'pending_sync']);
        $inspection->update(['sync_status' => 'pending_sync']);

        $this->syncService->enqueueDownloadForInspection($inspection->fresh(), 'upload_image');

        return $image;
    }

    public function submit(Inspection $inspection): Inspection
    {
        $inspection->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'sync_status' => 'pending_sync',
        ]);

        $inspection = $inspection->refresh();
        $this->syncService->enqueueDownloadForInspection($inspection, 'submit');

        return $inspection;
    }
}
