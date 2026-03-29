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
                    'title' => 'Site Audits',
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
                [
                    'title' => 'Workflow Settings',
                    'route' => 'admin.settings.incident-workflow',
                    'active_pattern' => 'admin.settings*',
                    'roles' => ['Admin'],
                    'icon' => 'checklist',
                ],
            ],
        ],
    ];
@endphp

<div class="flex min-h-[calc(100vh-7.5rem)] flex-col rounded-2xl ui-sidebar-bg p-4 shadow-xl shadow-teal-950/20">
    <div class="mb-5 rounded-xl ui-sidebar-surface px-3 py-3">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] ui-sidebar-muted">ISRMS</p>
        <p class="mt-1 text-sm font-semibold ui-sidebar-text">Safety Management</p>
    </div>

    <div class="flex-1 overflow-y-auto pr-1">
        @foreach ($menu as $section)
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] ui-sidebar-muted">
                {{ $section['label'] }}</p>

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
                                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition {{ $isActive ? 'ui-sidebar-active ui-sidebar-text shadow-sm shadow-black/10' : 'ui-sidebar-muted hover:bg-white/10 hover:text-white' }}">
                                <x-ui.icon :name="$item['icon'] ?? 'circle'" class="h-4 w-4" />
                                <span>{{ $item['title'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endforeach
    </div>

    @if ($user)
        <div class="mt-4 rounded-xl ui-sidebar-surface px-3 py-3">
            <p class="text-xs font-semibold ui-sidebar-text">{{ $user->name }}</p>
            <p class="text-[11px] uppercase tracking-[0.14em] ui-sidebar-muted">
                {{ $user->getRoleNames()->first() ?? 'User' }}</p>
        </div>
    @endif
</div>
