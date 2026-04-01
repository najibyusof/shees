@php
    $user = Auth::user();
    $roleLabel = $user?->roles?->pluck('name')?->join(', ') ?: 'User';

    $menu = [
        [
            'label' => 'Main',
            'items' => [
                [
                    'title' => 'Dashboard',
                    'route' => 'dashboard',
                    'icon' => 'dashboard',
                    'permission' => 'view_dashboard',
                ],
                ['title' => 'Profile', 'route' => 'profile.edit', 'icon' => 'profile'],
            ],
        ],
        [
            'label' => 'Incident',
            'can_any' => ['view_incident', 'create_incident', 'review_incident', 'approve_final'],
            'items' => [
                [
                    'title' => 'Incidents',
                    'route' => 'incidents.index',
                    'active_pattern' => 'incidents.*',
                    'permission' => 'view_incident',
                    'icon' => 'incident',
                ],
            ],
        ],
        [
            'label' => 'Training',
            'can_any' => ['view_training', 'create_training', 'edit_training', 'approve_training'],
            'items' => [
                [
                    'title' => 'Trainings',
                    'route' => 'trainings.index',
                    'active_pattern' => 'trainings.*',
                    'permission' => 'view_training',
                    'icon' => 'training',
                ],
            ],
        ],
        [
            'label' => 'Audit',
            'can_any' => ['view_audit', 'create_audit', 'edit_audit', 'approve_audit'],
            'items' => [
                [
                    'title' => 'Site Audits',
                    'route' => 'site-audits.index',
                    'active_pattern' => 'site-audits.*',
                    'permission' => 'view_audit',
                    'icon' => 'audit',
                ],
                [
                    'title' => 'Inspection Checklists',
                    'route' => 'inspection-checklists.index',
                    'active_pattern' => 'inspection-checklists.*',
                    'permission' => 'view_audit',
                    'icon' => 'checklist',
                ],
                [
                    'title' => 'Inspections',
                    'route' => 'inspections.index',
                    'active_pattern' => 'inspections.*',
                    'permission' => 'view_audit',
                    'icon' => 'inspection',
                ],
            ],
        ],
        [
            'label' => 'Worker',
            'can_any' => ['view_worker', 'create_worker', 'edit_worker', 'approve_worker'],
            'items' => [
                [
                    'title' => 'Worker Tracking',
                    'route' => 'worker-tracking.ui.index',
                    'active_pattern' => 'worker-tracking.ui.*',
                    'permission' => 'view_worker',
                    'icon' => 'profile',
                ],
            ],
        ],
        [
            'label' => 'Reports',
            'can_any' => ['view_report'],
            'items' => [
                [
                    'title' => 'Reports',
                    'route' => 'reports.index',
                    'active_pattern' => 'reports.*',
                    'permission' => 'view_report',
                    'icon' => 'audit',
                ],
                [
                    'title' => 'Audit Logs',
                    'route' => 'audit.logs',
                    'active_pattern' => 'audit.logs*',
                    'permission' => 'view_audit',
                    'icon' => 'audit',
                ],
            ],
        ],
        [
            'label' => 'Admin',
            'can_any' => ['view_user_management', 'roles.manage'],
            'items' => [
                [
                    'title' => 'User Management',
                    'route' => 'admin.users',
                    'active_pattern' => 'admin.users*',
                    'permission' => 'view_user_management',
                    'icon' => 'users',
                ],
                [
                    'title' => 'Roles & Permissions',
                    'route' => 'admin.roles',
                    'active_pattern' => 'admin.roles*',
                    'permission' => 'roles.manage',
                    'icon' => 'checklist',
                ],
                [
                    'title' => 'Workflow Settings',
                    'route' => 'admin.settings.incident-workflow',
                    'active_pattern' => 'admin.settings*',
                    'permission' => 'roles.manage',
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
            @if (isset($section['can_any']))
                @canany($section['can_any'])
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] ui-sidebar-muted">
                        {{ $section['label'] }}</p>

                    <ul class="mb-5 space-y-1 last:mb-0">
                        @foreach ($section['items'] as $item)
                            @php
                                $isActive = isset($item['active_pattern'])
                                    ? request()->routeIs($item['active_pattern'])
                                    : request()->routeIs($item['route']);
                            @endphp

                            @can($item['permission'])
                                <li>
                                    <a href="{{ route($item['route']) }}"
                                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition {{ $isActive ? 'ui-sidebar-active ui-sidebar-text shadow-sm shadow-black/10' : 'ui-sidebar-muted hover:bg-white/10 hover:text-white' }}">
                                        <x-ui.icon :name="$item['icon'] ?? 'circle'" class="h-4 w-4" />
                                        <span>{{ $item['title'] }}</span>
                                    </a>
                                </li>
                            @endcan
                        @endforeach
                    </ul>
                @endcanany
            @else
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] ui-sidebar-muted">
                    {{ $section['label'] }}</p>

                <ul class="mb-5 space-y-1 last:mb-0">
                    @foreach ($section['items'] as $item)
                        @php
                            $isActive = isset($item['active_pattern'])
                                ? request()->routeIs($item['active_pattern'])
                                : request()->routeIs($item['route']);
                        @endphp

                        @if (isset($item['permission']))
                            @can($item['permission'])
                                <li>
                                    <a href="{{ route($item['route']) }}"
                                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition {{ $isActive ? 'ui-sidebar-active ui-sidebar-text shadow-sm shadow-black/10' : 'ui-sidebar-muted hover:bg-white/10 hover:text-white' }}">
                                        <x-ui.icon :name="$item['icon'] ?? 'circle'" class="h-4 w-4" />
                                        <span>{{ $item['title'] }}</span>
                                    </a>
                                </li>
                            @endcan
                        @else
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
            @endif
        @endforeach
    </div>

    @if ($user)
        <div class="mt-4 rounded-xl ui-sidebar-surface px-3 py-3">
            <p class="text-xs font-semibold ui-sidebar-text">{{ $user->name }}</p>
            <p class="text-[11px] uppercase tracking-[0.14em] ui-sidebar-muted">
                {{ $roleLabel }}</p>
        </div>
    @endif
</div>
