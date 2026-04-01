<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('view_dashboard');

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'module' => ['nullable', 'string', 'in:all,incident,training,inspection,audit,worker'],
        ]);

        $payload = $this->dashboardService->buildApiDashboard(
            $request->user()->loadMissing('roles.permissions'),
            $filters
        );

        return $this->success($payload, 'Dashboard loaded successfully.');
    }

    public function analytics(Request $request): JsonResponse
    {
        Gate::authorize('view_dashboard');

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'module' => ['nullable', 'string', 'in:all,incident,training,inspection,audit,worker'],
        ]);

        $analytics = $this->dashboardService->buildAnalytics(
            $request->user()->loadMissing('roles.permissions'),
            $filters
        );

        return $this->success($analytics, 'Dashboard analytics loaded successfully.');
    }
}
