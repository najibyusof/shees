<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
        $this->middleware('permission:view_dashboard');
    }

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'module' => ['nullable', 'string', 'in:all,incident,training,inspection,audit,worker'],
        ]);

        $dashboard = $this->dashboardService->buildWebDashboard(
            $request->user()->loadMissing('roles.permissions'),
            $filters
        );

        return view('dashboard.index', [
            'dashboard' => $dashboard,
            'filters' => $filters,
        ]);
    }
}
