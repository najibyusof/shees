<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        ?int $userId,
        string $action,
        string $module,
        ?Model $auditable = null,
        ?array $metadata = null
    ): void {
        $contextMetadata = $this->requestContextMetadata();

        $finalMetadata = array_filter(
            array_merge($contextMetadata, $metadata ?? []),
            static fn ($value) => $value !== null && $value !== ''
        );

        try {
            AuditLog::query()->create([
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'auditable_type' => $auditable ? $auditable::class : null,
                'auditable_id' => $auditable?->getKey(),
                'metadata' => $finalMetadata ?: null,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Audit logging failed', [
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'auditable_type' => $auditable ? $auditable::class : null,
                'auditable_id' => $auditable?->getKey(),
                'request_id' => $contextMetadata['request_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function requestContextMetadata(): array
    {
        if (! app()->bound('request')) {
            return [];
        }

        /** @var Request $request */
        $request = request();

        $requestId = $request->attributes->get('request_id');
        if (! is_string($requestId) || trim($requestId) === '') {
            $requestId = $request->headers->get('X-Request-Id');
        }
        if (! is_string($requestId) || trim($requestId) === '') {
            $requestId = (string) Str::uuid();
            $request->attributes->set('request_id', $requestId);
        }

        $route = $request->route();

        return [
            'request_id' => $requestId,
            'http_method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route_name' => is_object($route) ? $route->getName() : null,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ];
    }
}
