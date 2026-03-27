<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\Worker;
use App\Notifications\WorkerGeofenceAlertNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class WorkerTrackingService
{
    public function createWorker(array $data): Worker
    {
        return Worker::query()->create([
            'user_id' => $data['user_id'] ?? null,
            'employee_code' => $data['employee_code'],
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'status' => $data['status'] ?? 'active',
            'geofence_center_latitude' => $data['geofence_center_latitude'] ?? null,
            'geofence_center_longitude' => $data['geofence_center_longitude'] ?? null,
            'geofence_radius_meters' => $data['geofence_radius_meters'] ?? 100,
        ]);
    }

    public function updateWorker(Worker $worker, array $data): Worker
    {
        $worker->update([
            'user_id' => $data['user_id'] ?? $worker->user_id,
            'employee_code' => $data['employee_code'] ?? $worker->employee_code,
            'full_name' => $data['full_name'] ?? $worker->full_name,
            'phone' => $data['phone'] ?? $worker->phone,
            'department' => $data['department'] ?? $worker->department,
            'position' => $data['position'] ?? $worker->position,
            'status' => $data['status'] ?? $worker->status,
            'geofence_center_latitude' => $data['geofence_center_latitude'] ?? $worker->geofence_center_latitude,
            'geofence_center_longitude' => $data['geofence_center_longitude'] ?? $worker->geofence_center_longitude,
            'geofence_radius_meters' => $data['geofence_radius_meters'] ?? $worker->geofence_radius_meters,
        ]);

        return $worker->refresh();
    }

    public function logAttendance(Worker $worker, array $data, ?User $recordedBy = null): AttendanceLog
    {
        return DB::transaction(function () use ($worker, $data, $recordedBy) {
            [$inside, $distance] = $this->evaluateGeofence(
                $worker,
                (float) $data['latitude'],
                (float) $data['longitude']
            );

            $alertMessage = null;
            $alertLevel = null;
            if ($inside === false) {
                $alertLevel = $distance > (($worker->geofence_radius_meters ?? 0) + 250) ? 'high' : 'medium';
                $alertMessage = 'Worker is outside configured geofence by '.$distance.' meters.';
            }

            $log = AttendanceLog::query()->create([
                'worker_id' => $worker->id,
                'recorded_by' => $recordedBy?->id,
                'event_type' => $data['event_type'] ?? 'ping',
                'logged_at' => $data['logged_at'] ?? now(),
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy_meters' => $data['accuracy_meters'] ?? null,
                'speed_mps' => $data['speed_mps'] ?? null,
                'heading_degrees' => $data['heading_degrees'] ?? null,
                'source' => $data['source'] ?? 'simulated',
                'device_identifier' => $data['device_identifier'] ?? null,
                'external_event_id' => $data['external_event_id'] ?? null,
                'inside_geofence' => $inside,
                'distance_from_geofence_meters' => $distance,
                'alert_level' => $alertLevel,
                'alert_message' => $alertMessage,
                'meta' => $data['meta'] ?? null,
            ]);

            $worker->update([
                'last_latitude' => (float) $data['latitude'],
                'last_longitude' => (float) $data['longitude'],
                'last_seen_at' => $log->logged_at,
            ]);

            if ($inside === false) {
                $this->notifyGeofenceAlert($log);
            }

            return $log->fresh(['worker', 'recorder']);
        });
    }

    public function simulatePing(Worker $worker, array $options = [], ?User $recordedBy = null): AttendanceLog
    {
        $baseLat = (float) ($worker->geofence_center_latitude ?? $worker->last_latitude ?? $options['base_latitude'] ?? 0.0);
        $baseLng = (float) ($worker->geofence_center_longitude ?? $worker->last_longitude ?? $options['base_longitude'] ?? 0.0);

        // Keep simulation predictable enough for testing while allowing occasional breaches.
        $breachChance = (float) ($options['breach_chance'] ?? 0.25);
        $simulateBreach = mt_rand(0, 1000) / 1000 <= $breachChance;

        $radius = (float) ($worker->geofence_radius_meters ?? 100);
        $offsetMeters = $simulateBreach
            ? $radius + (float) ($options['breach_offset_meters'] ?? 60)
            : (float) ($options['inside_offset_meters'] ?? min(20, max(5, $radius / 4)));

        $angle = deg2rad((float) mt_rand(0, 359));
        $latOffset = ($offsetMeters * cos($angle)) / 111320;
        $lngOffset = ($offsetMeters * sin($angle)) / (111320 * max(0.2, cos(deg2rad($baseLat))));

        return $this->logAttendance($worker, [
            'event_type' => $options['event_type'] ?? 'ping',
            'logged_at' => now(),
            'latitude' => round($baseLat + $latOffset, 7),
            'longitude' => round($baseLng + $lngOffset, 7),
            'accuracy_meters' => $options['accuracy_meters'] ?? 8.5,
            'speed_mps' => $options['speed_mps'] ?? null,
            'heading_degrees' => $options['heading_degrees'] ?? null,
            'source' => 'simulated',
            'device_identifier' => $options['device_identifier'] ?? 'simulator-1',
            'meta' => [
                'simulated' => true,
                'breach_mode' => $simulateBreach,
            ],
        ], $recordedBy);
    }

    /**
     * @return array{0: bool|null, 1: int|null}
     */
    private function evaluateGeofence(Worker $worker, float $latitude, float $longitude): array
    {
        if ($worker->geofence_center_latitude === null || $worker->geofence_center_longitude === null) {
            return [null, null];
        }

        $distance = (int) round($this->distanceMeters(
            (float) $worker->geofence_center_latitude,
            (float) $worker->geofence_center_longitude,
            $latitude,
            $longitude
        ));

        $inside = $distance <= (int) ($worker->geofence_radius_meters ?? 0);

        return [$inside, $distance];
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }

    private function notifyGeofenceAlert(AttendanceLog $log): void
    {
        $recipients = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Admin', 'Manager', 'Safety Officer']);
            })
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new WorkerGeofenceAlertNotification($log));
    }
}
