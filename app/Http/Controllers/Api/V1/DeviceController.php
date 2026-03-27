<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeviceRegistrationRequest;
use App\Models\DeviceRegistration;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/device/register
     *
     * Registers (or updates) the mobile device of the authenticated user.
     * Stores device metadata and an optional push token for future notification delivery.
     */
    public function register(DeviceRegistrationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        /** @var DeviceRegistration $device */
        $device = DeviceRegistration::updateOrCreate(
            [
                'user_id'   => $user->id,
                'device_id' => $data['device_id'],
            ],
            [
                'device_name' => $data['device_name'],
                'platform'    => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'push_token'  => $data['push_token'] ?? null,
                'is_active'   => true,
                'last_seen_at'=> now(),
            ]
        );

        return $this->success(
            data: [
                'id'          => $device->id,
                'device_id'   => $device->device_id,
                'device_name' => $device->device_name,
                'platform'    => $device->platform,
                'app_version' => $device->app_version,
                'registered_at'=> $device->created_at?->toIso8601String(),
            ],
            message: 'Device registered successfully.'
        );
    }

    /**
     * GET /api/v1/device/registrations
     *
     * Lists all registered devices for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $devices = DeviceRegistration::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn ($d) => [
                'id'           => $d->id,
                'device_id'    => $d->device_id,
                'device_name'  => $d->device_name,
                'platform'     => $d->platform,
                'app_version'  => $d->app_version,
                'last_seen_at' => $d->last_seen_at?->toIso8601String(),
            ]);

        return $this->success($devices);
    }

    /**
     * DELETE /api/v1/device/{deviceId}
     *
     * Deregisters a device (e.g. on logout or uninstall).
     */
    public function deregister(Request $request, string $deviceId): JsonResponse
    {
        $deleted = DeviceRegistration::where('user_id', $request->user()->id)
            ->where('device_id', $deviceId)
            ->update(['is_active' => false]);

        if (! $deleted) {
            return $this->notFound('Device not found.');
        }

        return $this->noContent('Device deregistered.');
    }
}
