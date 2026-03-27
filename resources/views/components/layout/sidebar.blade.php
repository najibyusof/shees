@php
    $user = Auth::user();
    $menu = [
        [
            'label' => 'Main',
            'items' => [
                ['title' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard'],
                ['title' => 'Profile', 'route' => 'profile.edit', 'icon' => 'profile'],
                [
                    'title' => 'Incidents',
                    'route' => 'incidents.index',
                    'active_pattern' => 'incidents.*',
                    'roles' => ['Admin', 'Manager', 'Safety Officer', 'Auditor', 'Supervisor', 'Worker'],
                    'icon' => 'incident',
                ],
                [
                    'title' => 'Trainings',
                    'route' => 'trainings.index',
                    'active_pattern' => 'trainings.*',
                    'roles' => ['Admin', 'Manager', 'Safety Officer', 'Auditor', 'Supervisor', 'Worker'],
                    'icon' => 'training',
                ],
                [
                    'title' => 'Inspection Checklists',
                    'route' => 'inspection-checklists.index',
                    'active_pattern' => 'inspection-checklists.*',
                    'roles' => ['Admin', 'Manager', 'Safety Officer', 'Auditor', 'Supervisor', 'Worker'],
                    'icon' => 'checklist',
                ],
                [
                    'title' => 'Inspections',
                    'route' => 'inspections.index',
                    'active_pattern' => 'inspections.*',
                    'roles' => ['Admin', 'Manager', 'Safety Officer', 'Auditor', 'Supervisor', 'Worker'],
                    'icon' => 'inspection',
                ],
                [
                    'title' => 'Audit & NCR',
                    'route' => 'site-audits.index',
                    'active_pattern' => 'site-audits.*',
                    'roles' => ['Admin', 'Manager', 'Safety Officer', 'Auditor', 'Supervisor', 'Worker'],
                    'icon' => 'audit',
                ],
                [
                    'title' => 'Worker Tracking',
                    'route' => 'worker-tracking.ui.index',
                    'active_pattern' => 'worker-tracking.ui.*',
                    'roles' => ['Admin', 'Manager', 'Safety Officer', 'Auditor', 'Supervisor', 'Worker'],
                    'icon' => 'profile',
                ],
                [
                    'title' => 'Reports',
                    'route' => 'reports.index',
                    'active_pattern' => 'reports.*',
                    'permission' => 'reports.view',
                    'icon' => 'audit',
                ],
            ],
        ],
        [
            'label' => 'Administration',
            'items' => [
                [
                    'title' => 'User Management',
                    'route' => 'admin.users',
                    'active_pattern' => 'admin.users*',
                    'roles' => ['Admin', 'Manager'],
                    'icon' => 'users',
                ],
                [
                    'title' => 'Roles & Permissions',
                    'route' => 'admin.roles',
                    'active_pattern' => 'admin.roles*',
                    'roles' => ['Admin'],
                    'permission' => 'roles.manage',
                    'icon' => 'checklist',
                ],
                [
                    'title' => 'Audit Logs',
                    'route' => 'audit.logs',
                    'active_pattern' => 'audit.logs*',
                    'permission' => 'audits.view',
                    'icon' => 'audit',
                ],
            ],
        ],
    ];
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div class="mb-4 rounded-xl bg-gradient-to-r from-slate-900 to-teal-800 p-4 text-white dark:from-gray-800 dark:to-teal-700">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-cyan-200">Workspace</p>
        <p class="mt-1 text-sm font-semibold">Safety, Security & Environmental</p>
    </div>

    @foreach ($menu as $section)
        <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] ui-text-muted">{{ $section['label'] }}</p>

        <ul class="mb-5 space-y-1 last:mb-0">
            @foreach ($section['items'] as $item)
                @php
                    $visible = true;

                    if (isset($item['roles'])) {
                        $visible = $user && $user->hasAnyRole($item['roles']);
                    }

                    if ($visible && isset($item['permission'])) {
                        $visible = $user && $user->hasPermissionTo($item['permission']);
                    }

                    $isActive = isset($item['active_pattern'])
                        ? request()->routeIs($item['active_pattern'])
                        : request()->routeIs($item['route']);
                @endphp

                @if ($visible)
                    <li>
                        <a href="{{ route($item['route']) }}"
                            class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition {{ $isActive ? 'bg-gradient-to-r from-slate-900 to-teal-800 text-white shadow-sm dark:from-gray-700 dark:to-teal-700' : 'ui-text-muted hover:ui-surface-soft hover:ui-text dark:hover:bg-gray-800' }}">
                            <x-ui.icon :name="$item['icon'] ?? 'circle'" class="h-4 w-4" />
                            <span>{{ $item['title'] }}</span>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    @endforeach
</div>
