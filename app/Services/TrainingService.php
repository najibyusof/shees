<?php

namespace App\Services;

use App\Events\TrainingExpiryDetected;
use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TrainingService
{
    public function create(array $data, User $actor): Training
    {
        return DB::transaction(function () use ($data, $actor) {
            $training = Training::query()->create($this->normalizeTrainingData($data));

            if (! empty($data['assigned_user_ids'])) {
                $this->assignUsers($training, $data['assigned_user_ids'], $actor);
            }

            return $training;
        });
    }

    public function update(Training $training, array $data): Training
    {
        $training->update($this->normalizeTrainingData($data));

        return $training->refresh();
    }

    public function assignUsers(Training $training, array $userIds, User $assignedBy): void
    {
        $syncData = [];

        foreach ($userIds as $userId) {
            $syncData[$userId] = [
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(),
                'completion_status' => 'assigned',
                'completed_at' => null,
            ];
        }

        $training->users()->syncWithoutDetaching($syncData);
    }

    public function markCompletion(Training $training, int $userId, string $completionStatus): void
    {
        $completedAt = $completionStatus === 'completed' ? now() : null;

        $training->users()->updateExistingPivot($userId, [
            'completion_status' => $completionStatus,
            'completed_at' => $completedAt,
        ]);
    }

    public function uploadCertificate(
        Training $training,
        int $userId,
        UploadedFile $file,
        User $uploadedBy,
        ?string $issuedAt = null,
        ?string $expiresAt = null
    ): Certificate {
        $path = $file->store('certificates', 'public');

        return Certificate::query()->create([
            'training_id' => $training->id,
            'user_id' => $userId,
            'uploaded_by' => $uploadedBy->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?? 0,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'metadata' => [
                'uploaded_by_name' => $uploadedBy->name,
            ],
        ]);
    }

    public function notifyExpiringCertificates(int $daysAhead = 30): int
    {
        $start = now()->startOfDay();
        $end = now()->addDays($daysAhead)->endOfDay();

        $certificates = Certificate::query()
            ->with(['user', 'training'])
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$start->toDateString(), $end->toDateString()])
            ->whereNull('expiry_notified_at')
            ->get();

        foreach ($certificates as $certificate) {
            if (! $certificate->user) {
                continue;
            }

            event(new TrainingExpiryDetected($certificate));
        }

        return $certificates->count();
    }

    private function normalizeTrainingData(array $data): array
    {
        $titleTranslations = $data['title_translations'] ?? [];
        $descriptionTranslations = $data['description_translations'] ?? [];

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'title_translations' => array_filter($titleTranslations, fn ($value) => filled($value)),
            'description_translations' => array_filter($descriptionTranslations, fn ($value) => filled($value)),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'certificate_validity_days' => $data['certificate_validity_days'] ?? 365,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
