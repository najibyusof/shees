@extends('layouts.app')

@section('header')
    <div
        class="rounded-2xl bg-gradient-to-r from-teal-700 via-cyan-700 to-sky-700 px-5 py-4 text-white shadow-lg shadow-teal-900/20 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">{{ $dashboard['active_role'] }} Dashboard</h1>
                <p class="mt-1 text-sm text-teal-100">Permission-driven operational summary for
                    {{ now()->format('l, d M Y') }}</p>
            </div>
            <div class="rounded-lg bg-white/15 px-3 py-2 text-xs font-semibold uppercase tracking-wide">
                Roles: {{ implode(', ', $dashboard['roles']) }}
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $analytics = $dashboard['analytics'] ?? [];
        $incidentAnalytics = $analytics['incident'] ?? [];
        $trainingAnalytics = $analytics['training'] ?? [];
        $inspectionAnalytics = $analytics['inspection'] ?? [];
        $auditAnalytics = $analytics['audit'] ?? [];
        $workerAnalytics = $analytics['worker'] ?? [];
        $incidentMom = $incidentAnalytics['mom_comparison'] ?? null;
        $trainingMom = $trainingAnalytics['mom_comparison'] ?? null;
        $auditMom = $auditAnalytics['mom_comparison'] ?? null;
        $workerMom = $workerAnalytics['mom_comparison'] ?? null;
        $chartProfile = $dashboard['chart_profile'] ?? [];

        $moduleFilter = $filters['module'] ?? request('module', 'all');
        $availableModules = array_keys($analytics);

        $canIncidentAnalytics =
            auth()
                ->user()
                ?->canAny(['view_incident_analytics', 'view_incident']) && array_key_exists('incident', $analytics);
        $canTrainingAnalytics =
            auth()
                ->user()
                ?->canAny(['view_training_analytics', 'view_training']) && array_key_exists('training', $analytics);
        $canInspectionAnalytics =
            auth()
                ->user()
                ?->canAny(['view_inspection_analytics', 'view_audit']) && array_key_exists('inspection', $analytics);
        $canAuditAnalytics =
            auth()
                ->user()
                ?->canAny(['view_audit_analytics', 'view_audit']) && array_key_exists('audit', $analytics);
        $canWorkerAnalytics =
            auth()
                ->user()
                ?->canAny(['view_worker_analytics', 'view_worker']) && array_key_exists('worker', $analytics);

        $trainingExpiryCount = (int) ($trainingAnalytics['expiring_certificates'] ?? 0);
        $trainingTotal = (int) ($trainingAnalytics['total_trainings'] ?? 0);
        $trainingHealthData = [
            'labels' => ['Expiring', 'Stable'],
            'data' => [$trainingExpiryCount, max($trainingTotal - $trainingExpiryCount, 0)],
        ];

        $activeRoleSlug = strtolower((string) ($dashboard['active_role'] ?? ''));
        $activeRoleKey = \Illuminate\Support\Str::slug($activeRoleSlug);
        $isAdminDashboard = $activeRoleKey === 'admin';
        $isManagerDashboard = $activeRoleKey === 'manager';
        $isSupervisorDashboard = $activeRoleKey === 'supervisor';
        $isSafetyOfficerDashboard = $activeRoleKey === 'safety-officer';
        $isHodHsseDashboard = $activeRoleKey === 'hod-hsse';
        $isApsbPdDashboard = $activeRoleKey === 'apsb-pd';
        $isManagerLikeDashboard =
            $isManagerDashboard ||
            $isSupervisorDashboard ||
            $isSafetyOfficerDashboard ||
            $isHodHsseDashboard ||
            $isApsbPdDashboard;

        $canShowChart = function (string $module, string $chart) use ($chartProfile): bool {
            if (empty($chartProfile)) {
                return true;
            }

            $moduleCharts = $chartProfile[$module] ?? [];

            return in_array('*', $moduleCharts, true) || in_array($chart, $moduleCharts, true);
        };

        $incidentChartKeys = [
            'by_status',
            'by_classification',
            'top_locations',
            'over_time',
            'weekly_trend',
            'top_types',
        ];
        $trainingChartKeys = ['by_status', 'certificate_expiry_health'];
        $inspectionChartKeys = ['passed_vs_failed', 'trends'];
        $auditChartKeys = ['ncr_by_severity', 'open_vs_closed_ncr'];
        $workerChartKeys = ['attendance_trends', 'active_vs_inactive', 'weekly_attendance'];

        $hasVisibleCharts = function (string $module, array $chartKeys) use ($canShowChart): bool {
            foreach ($chartKeys as $chartKey) {
                if ($canShowChart($module, $chartKey)) {
                    return true;
                }
            }

            return false;
        };

        $countVisibleCharts = function (string $module, array $chartKeys) use ($canShowChart): int {
            return collect($chartKeys)
                ->filter(fn(string $chartKey): bool => $canShowChart($module, $chartKey))
                ->count();
        };

        $showIncidentSection = $canIncidentAnalytics && $hasVisibleCharts('incident', $incidentChartKeys);
        $showTrainingSection = $canTrainingAnalytics && $hasVisibleCharts('training', $trainingChartKeys);
        $showInspectionSection = $canInspectionAnalytics && $hasVisibleCharts('inspection', $inspectionChartKeys);
        $showAuditSection = $canAuditAnalytics && $hasVisibleCharts('audit', $auditChartKeys);
        $showWorkerSection = $canWorkerAnalytics && $hasVisibleCharts('worker', $workerChartKeys);

        $incidentVisibleCount = $showIncidentSection ? $countVisibleCharts('incident', $incidentChartKeys) : 0;
        $trainingVisibleCount = $showTrainingSection ? $countVisibleCharts('training', $trainingChartKeys) : 0;
        $inspectionVisibleCount = $showInspectionSection ? $countVisibleCharts('inspection', $inspectionChartKeys) : 0;
        $auditVisibleCount = $showAuditSection ? $countVisibleCharts('audit', $auditChartKeys) : 0;
        $workerVisibleCount = $showWorkerSection ? $countVisibleCharts('worker', $workerChartKeys) : 0;

        $totalVisibleCharts =
            $incidentVisibleCount +
            $trainingVisibleCount +
            $inspectionVisibleCount +
            $auditVisibleCount +
            $workerVisibleCount;

        $shouldGroupBySections = $totalVisibleCharts > 6;
        $shouldUseMasonryRhythm = $totalVisibleCharts >= 10 && !$isManagerLikeDashboard;

        $visibleSectionCount = collect([
            $showIncidentSection,
            $showTrainingSection,
            $showInspectionSection,
            $showAuditSection,
            $showWorkerSection,
        ])
            ->filter()
            ->count();

        $resolveSectionSpanClass = function (int $visibleCharts) use (
            $shouldGroupBySections,
            $visibleSectionCount,
        ): string {
            if ($visibleCharts < 3 || !$shouldGroupBySections) {
                return 'col-span-12';
            }

            return $visibleSectionCount > 1 ? 'col-span-12 2xl:col-span-6' : 'col-span-12';
        };

        $incidentSectionSpanClass =
            $isAdminDashboard || $isManagerLikeDashboard
                ? 'col-span-12'
                : $resolveSectionSpanClass($incidentVisibleCount);
        $trainingSectionSpanClass = $isManagerLikeDashboard
            ? 'col-span-12'
            : $resolveSectionSpanClass($trainingVisibleCount);
        $inspectionSectionSpanClass = $isManagerLikeDashboard
            ? 'col-span-12'
            : $resolveSectionSpanClass($inspectionVisibleCount);
        $auditSectionSpanClass = $isManagerLikeDashboard ? 'col-span-12' : $resolveSectionSpanClass($auditVisibleCount);
        $workerSectionSpanClass =
            $isAdminDashboard || $isManagerLikeDashboard
                ? 'col-span-12'
                : $resolveSectionSpanClass($workerVisibleCount);

        $resolveCardSize = function (int $visibleCharts, string $defaultSize = 'sm'): string {
            if ($visibleCharts <= 1) {
                return 'lg';
            }

            if ($visibleCharts < 3) {
                return 'md';
            }

            return $defaultSize;
        };

        $incidentCompactDefaultSize = $isManagerLikeDashboard ? 'md' : 'sm';
        $incidentCompactSize = $resolveCardSize($incidentVisibleCount, $incidentCompactDefaultSize);
        $incidentTrendSize = $resolveCardSize($incidentVisibleCount, 'md');
        $trainingCardSize = $resolveCardSize($trainingVisibleCount, 'md');
        $inspectionCardSize = $resolveCardSize($inspectionVisibleCount, 'sm');
        $auditCardSize = $resolveCardSize($auditVisibleCount, 'sm');
        $workerCardSize = $resolveCardSize($workerVisibleCount, 'md');

        $incidentStatusSize =
            $isAdminDashboard || $isManagerLikeDashboard
                ? 'md'
                : ($shouldUseMasonryRhythm && $incidentVisibleCount > 3
                    ? 'sm'
                    : $incidentCompactSize);
        $incidentClassificationSize =
            $isAdminDashboard || $isManagerLikeDashboard
                ? 'md'
                : ($shouldUseMasonryRhythm && $incidentVisibleCount > 3
                    ? 'sm'
                    : $incidentCompactSize);
        $incidentTopLocationsSize =
            $isAdminDashboard || $isManagerLikeDashboard
                ? 'md'
                : ($shouldUseMasonryRhythm && $incidentVisibleCount > 3
                    ? 'sm'
                    : $incidentCompactSize);
        $incidentOverTimeSize =
            $isAdminDashboard || $isManagerLikeDashboard
                ? 'lg'
                : ($shouldUseMasonryRhythm && $incidentVisibleCount > 3
                    ? 'lg'
                    : $incidentTrendSize);
        $incidentWeeklyTrendSize = $isAdminDashboard
            ? 'md'
            : ($isHodHsseDashboard || $isApsbPdDashboard
                ? 'lg'
                : ($isManagerLikeDashboard
                    ? 'md'
                    : ($shouldUseMasonryRhythm && $incidentVisibleCount > 4
                        ? 'md'
                        : $incidentTrendSize)));
        $incidentTopTypesSize =
            $isAdminDashboard || $isManagerLikeDashboard
                ? 'md'
                : ($shouldUseMasonryRhythm && $incidentVisibleCount > 3
                    ? 'sm'
                    : $incidentCompactSize);

        $trainingStatusSize = $shouldUseMasonryRhythm && $trainingVisibleCount > 1 ? 'md' : $trainingCardSize;
        $trainingHealthSize = $shouldUseMasonryRhythm && $trainingVisibleCount > 1 ? 'md' : $trainingCardSize;

        $inspectionPassedFailedSize = $isAdminDashboard
            ? 'md'
            : ($shouldUseMasonryRhythm && $inspectionVisibleCount > 1
                ? 'sm'
                : $inspectionCardSize);
        $inspectionTrendsSize = $isAdminDashboard
            ? 'md'
            : ($shouldUseMasonryRhythm && $inspectionVisibleCount > 1
                ? 'md'
                : $inspectionCardSize);

        $auditSeveritySize = $isAdminDashboard
            ? 'md'
            : ($shouldUseMasonryRhythm && $auditVisibleCount > 1
                ? 'sm'
                : $auditCardSize);
        $auditOpenClosedSize = $isAdminDashboard
            ? 'md'
            : ($shouldUseMasonryRhythm && $auditVisibleCount > 1
                ? 'sm'
                : $auditCardSize);

        $workerAttendanceTrendSize = $isAdminDashboard
            ? 'md'
            : ($shouldUseMasonryRhythm && $workerVisibleCount > 2
                ? 'md'
                : $workerCardSize);
        $workerActiveInactiveSize = $isAdminDashboard
            ? 'md'
            : ($shouldUseMasonryRhythm && $workerVisibleCount > 2
                ? 'sm'
                : $workerCardSize);
        $workerWeeklyAttendanceSize =
            $isAdminDashboard || $isManagerLikeDashboard
                ? 'lg'
                : ($shouldUseMasonryRhythm && $workerVisibleCount > 2
                    ? 'lg'
                    : $workerCardSize);

        $incidentStandardHeight = $isAdminDashboard || $isManagerLikeDashboard ? '220px' : '200px';
        $incidentFeaturedHeight = $isAdminDashboard || $isManagerLikeDashboard ? '240px' : '220px';
        $workerStandardHeight = $isAdminDashboard || $isManagerLikeDashboard ? '220px' : '210px';
        $workerFeaturedHeight = '220px';
        $incidentCardMinHeight = $isAdminDashboard || $isManagerLikeDashboard ? '270px' : '210px';
        $workerCardMinHeight = $isAdminDashboard || $isManagerLikeDashboard ? '260px' : '210px';

        $sectionGridClass = $shouldUseMasonryRhythm
            ? 'grid grid-cols-12 gap-4 items-start auto-rows-auto'
            : 'grid grid-cols-12 gap-4';

        $visibleWidgets = collect($dashboard['widgets'] ?? [])
            ->filter(function (array $widget): bool {
                $permission = $widget['permission'] ?? null;

                return $permission ? (bool) auth()->user()?->can($permission) : true;
            })
            ->values();

        $visibleWidgetCount = $visibleWidgets->count();
        $widgetGridClass =
            $visibleWidgetCount === 5
                ? 'grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5'
                : 'grid grid-cols-12 gap-4';
        $widgetColumnClass = match (true) {
            $visibleWidgetCount === 5 => 'col-span-1',
            $visibleWidgetCount <= 1 => 'col-span-12',
            $visibleWidgetCount === 2 => 'col-span-12 sm:col-span-6',
            $visibleWidgetCount === 3 => 'col-span-12 sm:col-span-6 xl:col-span-4',
            default => 'col-span-12 sm:col-span-6 xl:col-span-3',
        };
    @endphp

    @once
        <style>
            .analytics-reveal {
                opacity: 0;
                transform: translateY(10px);
            }

            .analytics-reveal.is-visible {
                opacity: 1;
                transform: translateY(0);
                transition: opacity 320ms ease, transform 320ms ease;
            }
        </style>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('dashboardSectionToggle', (role, section, persist) => ({
                    open: true,
                    storageKey: null,
                    init() {
                        if (!persist) {
                            this.open = true;
                            return;
                        }

                        const roleKey = String(role || 'role').trim().toLowerCase().replace(/\s+/g, '-');
                        this.storageKey = `dashboard-section:${roleKey}:${section}`;

                        const stored = window.localStorage.getItem(this.storageKey);
                        this.open = stored === null ? true : stored === '1';
                    },
                    toggle() {
                        this.open = !this.open;

                        if (persist && this.storageKey) {
                            window.localStorage.setItem(this.storageKey, this.open ? '1' : '0');
                        }

                        window.dispatchEvent(new CustomEvent('dashboard:section-visibility-changed', {
                            detail: {
                                section,
                                open: this.open,
                            },
                        }));
                    },
                }));
            });

            (function() {
                const initReveal = () => {
                    const revealNodes = document.querySelectorAll('[data-analytics-reveal]');
                    if (!revealNodes.length) {
                        return;
                    }

                    if (!('IntersectionObserver' in window)) {
                        revealNodes.forEach((node) => node.classList.add('is-visible'));
                        return;
                    }

                    const observer = new IntersectionObserver((entries, activeObserver) => {
                        entries.forEach((entry) => {
                            if (!entry.isIntersecting) {
                                return;
                            }

                            const delay = Number(entry.target.getAttribute('data-reveal-delay') || 0);
                            window.setTimeout(() => {
                                entry.target.classList.add('is-visible');
                            }, Number.isFinite(delay) ? delay : 0);

                            activeObserver.unobserve(entry.target);
                        });
                    }, {
                        threshold: 0.15,
                        rootMargin: '40px 0px',
                    });

                    revealNodes.forEach((node) => observer.observe(node));
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initReveal, {
                        once: true
                    });
                } else {
                    initReveal();
                }
            })
            ();
        </script>
    @endonce

    <div class="space-y-6" x-data="{ loadingWidgets: true }" x-init="setTimeout(() => loadingWidgets = false, 250)">
        <div class="rounded-2xl border ui-border bg-white p-4 shadow-sm dark:bg-gray-900 sm:p-5">
            <form method="GET" action="{{ route('dashboard') }}" class="grid gap-3 md:grid-cols-4">
                <div>
                    <label for="from"
                        class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] ?? request('from') }}"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                </div>
                <div>
                    <label for="to"
                        class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] ?? request('to') }}"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                </div>
                <div>
                    <label for="module"
                        class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Module</label>
                    <select id="module" name="module"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                        <option value="all" @selected($moduleFilter === 'all')>All Modules</option>
                        @if (in_array('incident', $availableModules, true))
                            <option value="incident" @selected($moduleFilter === 'incident')>Incidents</option>
                        @endif
                        @if (in_array('training', $availableModules, true))
                            <option value="training" @selected($moduleFilter === 'training')>Trainings</option>
                        @endif
                        @if (in_array('inspection', $availableModules, true))
                            <option value="inspection" @selected($moduleFilter === 'inspection')>Inspections</option>
                        @endif
                        @if (in_array('audit', $availableModules, true))
                            <option value="audit" @selected($moduleFilter === 'audit')>Audits & NCR</option>
                        @endif
                        @if (in_array('worker', $availableModules, true))
                            <option value="worker" @selected($moduleFilter === 'worker')>Workers</option>
                        @endif
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400">
                        Apply Filters
                    </button>
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="{{ $widgetGridClass }}">
            <template x-if="loadingWidgets">
                <div class="contents">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="{{ $widgetColumnClass }}">
                            <x-dashboard-card loading="true" />
                        </div>
                    @endfor
                </div>
            </template>

            <div class="contents" x-show="!loadingWidgets" x-cloak>
                @foreach ($visibleWidgets as $widget)
                    @php
                        $widgetIcon = match (strtolower($widget['module'])) {
                            'incident' => 'incident',
                            'training' => 'training',
                            'audit' => 'audit',
                            'worker' => 'users',
                            'admin' => 'users',
                            'reports' => 'dashboard',
                            default => 'dashboard',
                        };

                        $key = strtolower($widget['key'] ?? '');
                        $widgetTrend =
                            $widget['trend'] ??
                            (str_contains($key, 'overdue') ||
                            str_contains($key, 'breach') ||
                            str_contains($key, 'queue')
                                ? ['direction' => 'up', 'label' => 'Attention']
                                : ['direction' => 'neutral', 'label' => 'Live']);
                    @endphp
                    <div class="{{ $widgetColumnClass }}">
                        <x-dashboard-card :icon="$widgetIcon" :value="$widget['value']" :label="$widget['label']" :module="$widget['module']"
                            :trend="$widgetTrend" />
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            @canany(['view_incident_analytics', 'view_incident'])
                @if ($showIncidentSection)
                    <section x-data="dashboardSectionToggle('{{ $dashboard['active_role'] }}', 'incident', {{ $isAdminDashboard ? 'true' : 'false' }})" data-analytics-reveal data-reveal-delay="0"
                        class="analytics-reveal {{ $incidentSectionSpanClass }} rounded-2xl border ui-border bg-white p-4 shadow-sm dark:bg-gray-900 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold ui-text">Incident Analytics</h2>
                                <p class="text-sm text-slate-500">Total incidents:
                                    {{ number_format((int) ($incidentAnalytics['total_incidents'] ?? 0)) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($incidentMom)
                                    @php
                                        $direction = $incidentMom['direction'] ?? 'neutral';
                                        $pct = number_format((float) ($incidentMom['change_pct'] ?? 0), 1);
                                        $arrow = $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '→');
                                        $badgeClass =
                                            $direction === 'up'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                : ($direction === 'down'
                                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                                                    : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300');
                                    @endphp
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                                        {{ $arrow }} {{ $pct }}% vs
                                        {{ $incidentMom['previous_label'] ?? 'Prev month' }}
                                    </span>
                                @endif
                                @if ($isAdminDashboard)
                                    <button type="button" @click="toggle()"
                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                        x-text="open ? 'Collapse' : 'Expand'"></button>
                                @endif
                            </div>
                        </div>
                        <div class="mt-4 {{ $sectionGridClass }}" x-show="open" x-transition.opacity.duration.200ms x-cloak>
                            @if ($canShowChart('incident', 'by_status'))
                                <x-analytics-card title="Incidents by Status" type="pie" :data="$incidentAnalytics['by_status'] ?? ['labels' => [], 'data' => []]" label="Incidents"
                                    :size="$incidentStatusSize" :expand="$incidentVisibleCount === 1" :height="$incidentStandardHeight" :body-min-height="$incidentCardMinHeight" :reveal-delay="0"
                                    lazy="true" />
                            @endif
                            @if ($canShowChart('incident', 'by_classification'))
                                <x-analytics-card title="Incidents by Classification" type="bar" :data="$incidentAnalytics['by_classification'] ?? ['labels' => [], 'data' => []]"
                                    label="Incidents" :size="$incidentClassificationSize" :expand="$incidentVisibleCount === 1" :height="$incidentStandardHeight"
                                    :body-min-height="$incidentCardMinHeight" :reveal-delay="60" lazy="true" />
                            @endif
                            @if ($canShowChart('incident', 'top_locations') && !$isApsbPdDashboard)
                                <x-analytics-card title="Top 5 Locations" type="bar" :data="$incidentAnalytics['top_locations'] ?? ['labels' => [], 'data' => []]" label="Locations"
                                    :size="$incidentTopLocationsSize" :expand="$incidentVisibleCount === 1" :height="$incidentStandardHeight" :body-min-height="$incidentCardMinHeight"
                                    :reveal-delay="120" lazy="true" />
                            @endif
                            @if ($canShowChart('incident', 'over_time'))
                                <x-analytics-card title="Incidents Over Time" type="line" :data="$incidentAnalytics['over_time'] ?? ['labels' => [], 'data' => []]"
                                    label="Incidents" :size="$incidentOverTimeSize" :expand="$incidentVisibleCount === 1" :height="$incidentFeaturedHeight"
                                    :body-min-height="$incidentCardMinHeight" :reveal-delay="180" lazy="true" />
                            @endif
                            @if ($canShowChart('incident', 'weekly_trend'))
                                <x-analytics-card title="Weekly Incident Trend" type="line" :data="$incidentAnalytics['weekly_trend'] ?? ['labels' => [], 'data' => []]"
                                    label="Weekly incidents" :size="$incidentWeeklyTrendSize" :expand="$incidentVisibleCount === 1" :height="$incidentStandardHeight"
                                    :body-min-height="$incidentCardMinHeight" :reveal-delay="240" lazy="true" />
                            @endif
                            @if ($canShowChart('incident', 'top_types') && !$isApsbPdDashboard)
                                <x-analytics-card title="Top 5 Incident Types" type="bar" :data="$incidentAnalytics['top_types'] ?? ['labels' => [], 'data' => []]" label="Types"
                                    :size="$incidentTopTypesSize" :expand="$incidentVisibleCount === 1" :height="$incidentStandardHeight" :body-min-height="$incidentCardMinHeight"
                                    :reveal-delay="300" lazy="true" />
                            @endif
                            @if ($isApsbPdDashboard && $canShowChart('incident', 'top_locations'))
                                <x-analytics-card title="Top 5 Locations" type="bar" :data="$incidentAnalytics['top_locations'] ?? ['labels' => [], 'data' => []]" label="Locations"
                                    :size="$incidentTopLocationsSize" :expand="$incidentVisibleCount === 1" :height="$incidentStandardHeight" :body-min-height="$incidentCardMinHeight"
                                    :reveal-delay="300" lazy="true" />
                            @endif
                            @if ($isApsbPdDashboard && $canShowChart('incident', 'top_types'))
                                <x-analytics-card title="Top 5 Incident Types" type="bar" :data="$incidentAnalytics['top_types'] ?? ['labels' => [], 'data' => []]" label="Types"
                                    :size="$incidentTopTypesSize" :expand="$incidentVisibleCount === 1" :height="$incidentStandardHeight" :body-min-height="$incidentCardMinHeight"
                                    :reveal-delay="360" lazy="true" />
                            @endif
                        </div>
                    </section>
                @endif
            @endcanany

            @canany(['view_training_analytics', 'view_training'])
                @if ($showTrainingSection)
                    <section x-data="dashboardSectionToggle('{{ $dashboard['active_role'] }}', 'training', {{ $isAdminDashboard ? 'true' : 'false' }})" data-analytics-reveal data-reveal-delay="80"
                        class="analytics-reveal {{ $trainingSectionSpanClass }} rounded-2xl border ui-border bg-white p-4 shadow-sm dark:bg-gray-900 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold ui-text">Training Analytics</h2>
                                <p class="text-sm text-slate-500">
                                    Total trainings: {{ number_format($trainingTotal) }}
                                    | Completion: {{ number_format((float) ($trainingAnalytics['completion_rate'] ?? 0), 1) }}%
                                    | Expiring certificates: {{ number_format($trainingExpiryCount) }}
                                    @if ($trainingMom)
                                        |
                                        {{ ($trainingMom['direction'] ?? 'neutral') === 'up' ? '↑' : (($trainingMom['direction'] ?? 'neutral') === 'down' ? '↓' : '→') }}
                                        {{ number_format((float) ($trainingMom['change_pct'] ?? 0), 1) }}% MoM
                                    @endif
                                </p>
                            </div>
                            @if ($isAdminDashboard)
                                <button type="button" @click="toggle()"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                    x-text="open ? 'Collapse' : 'Expand'"></button>
                            @endif
                        </div>
                        <div class="mt-4 {{ $sectionGridClass }}" x-show="open" x-transition.opacity.duration.200ms x-cloak>
                            @if ($canShowChart('training', 'by_status'))
                                <x-analytics-card title="Training Status" type="bar" :data="$trainingAnalytics['by_status'] ?? ['labels' => [], 'data' => []]" label="Trainings"
                                    :size="$trainingStatusSize" :expand="$trainingVisibleCount === 1" height="190px" :reveal-delay="0" lazy="true" />
                            @endif
                            @if ($canShowChart('training', 'certificate_expiry_health'))
                                <x-analytics-card title="Certificate Expiry Health" type="doughnut" :data="$trainingHealthData"
                                    label="Certificates" :size="$trainingHealthSize" :expand="$trainingVisibleCount === 1" height="190px"
                                    :reveal-delay="80" lazy="true" />
                            @endif
                        </div>
                    </section>
                @endif
            @endcanany

            @canany(['view_inspection_analytics', 'view_audit'])
                @if ($showInspectionSection)
                    <section x-data="dashboardSectionToggle('{{ $dashboard['active_role'] }}', 'inspection', {{ $isAdminDashboard ? 'true' : 'false' }})" data-analytics-reveal data-reveal-delay="120"
                        class="analytics-reveal {{ $inspectionSectionSpanClass }} rounded-2xl border ui-border bg-white p-4 shadow-sm dark:bg-gray-900 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold ui-text">Inspection Analytics</h2>
                                <p class="text-sm text-slate-500">Total inspections:
                                    {{ number_format((int) ($inspectionAnalytics['total_inspections'] ?? 0)) }}</p>
                            </div>
                            @if ($isAdminDashboard)
                                <button type="button" @click="toggle()"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                    x-text="open ? 'Collapse' : 'Expand'"></button>
                            @endif
                        </div>
                        <div class="mt-4 {{ $sectionGridClass }}" x-show="open" x-transition.opacity.duration.200ms x-cloak>
                            @if ($canShowChart('inspection', 'passed_vs_failed'))
                                <x-analytics-card title="Passed vs Failed" type="pie" :data="$inspectionAnalytics['passed_vs_failed'] ?? ['labels' => [], 'data' => []]"
                                    label="Inspections" :size="$inspectionPassedFailedSize" :expand="$inspectionVisibleCount === 1" height="200px"
                                    :reveal-delay="0" lazy="true" />
                            @endif
                            @if ($canShowChart('inspection', 'trends'))
                                <x-analytics-card title="Inspection Trends" type="line" :data="$inspectionAnalytics['trends'] ?? ['labels' => [], 'data' => []]"
                                    label="Inspections" :size="$inspectionTrendsSize" :expand="$inspectionVisibleCount === 1" height="210px"
                                    :reveal-delay="80" lazy="true" />
                            @endif
                        </div>
                    </section>
                @endif
            @endcanany

            @canany(['view_audit_analytics', 'view_audit'])
                @if ($showAuditSection)
                    <section x-data="dashboardSectionToggle('{{ $dashboard['active_role'] }}', 'audit', {{ $isAdminDashboard ? 'true' : 'false' }})" data-analytics-reveal data-reveal-delay="180"
                        class="analytics-reveal {{ $auditSectionSpanClass }} rounded-2xl border ui-border bg-white p-4 shadow-sm dark:bg-gray-900 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold ui-text">Audit & NCR Analytics</h2>
                                <p class="text-sm text-slate-500">
                                    Total audits: {{ number_format((int) ($auditAnalytics['total_audits'] ?? 0)) }}
                                    @if ($auditMom)
                                        |
                                        {{ ($auditMom['direction'] ?? 'neutral') === 'up' ? '↑' : (($auditMom['direction'] ?? 'neutral') === 'down' ? '↓' : '→') }}
                                        {{ number_format((float) ($auditMom['change_pct'] ?? 0), 1) }}% MoM
                                    @endif
                                </p>
                            </div>
                            @if ($isAdminDashboard)
                                <button type="button" @click="toggle()"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                    x-text="open ? 'Collapse' : 'Expand'"></button>
                            @endif
                        </div>
                        <div class="mt-4 {{ $sectionGridClass }}" x-show="open" x-transition.opacity.duration.200ms x-cloak>
                            @if ($canShowChart('audit', 'ncr_by_severity'))
                                <x-analytics-card title="NCR by Severity" type="bar" :data="$auditAnalytics['ncr_by_severity'] ?? ['labels' => [], 'data' => []]" label="NCR"
                                    :size="$auditSeveritySize" :expand="$auditVisibleCount === 1" height="200px" :reveal-delay="0" lazy="true" />
                            @endif
                            @if ($canShowChart('audit', 'open_vs_closed_ncr'))
                                <x-analytics-card title="Open vs Closed NCR" type="pie" :data="$auditAnalytics['open_vs_closed_ncr'] ?? ['labels' => [], 'data' => []]" label="NCR"
                                    :size="$auditOpenClosedSize" :expand="$auditVisibleCount === 1" height="200px" :reveal-delay="80" lazy="true" />
                            @endif
                        </div>
                    </section>
                @endif
            @endcanany

            @canany(['view_worker_analytics', 'view_worker'])
                @if ($showWorkerSection)
                    <section x-data="dashboardSectionToggle('{{ $dashboard['active_role'] }}', 'worker', {{ $isAdminDashboard ? 'true' : 'false' }})" data-analytics-reveal data-reveal-delay="240"
                        class="analytics-reveal {{ $workerSectionSpanClass }} rounded-2xl border ui-border bg-white p-4 shadow-sm dark:bg-gray-900 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold ui-text">Worker Analytics</h2>
                                <p class="text-sm text-slate-500">
                                    Total workers: {{ number_format((int) ($workerAnalytics['total_workers'] ?? 0)) }}
                                    @if ($workerMom)
                                        |
                                        {{ ($workerMom['direction'] ?? 'neutral') === 'up' ? '↑' : (($workerMom['direction'] ?? 'neutral') === 'down' ? '↓' : '→') }}
                                        {{ number_format((float) ($workerMom['change_pct'] ?? 0), 1) }}% MoM
                                    @endif
                                </p>
                            </div>
                            @if ($isAdminDashboard)
                                <button type="button" @click="toggle()"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                    x-text="open ? 'Collapse' : 'Expand'"></button>
                            @endif
                        </div>
                        <div class="mt-4 {{ $sectionGridClass }}" x-show="open" x-transition.opacity.duration.200ms x-cloak>
                            @if ($canShowChart('worker', 'attendance_trends'))
                                <x-analytics-card title="Attendance Trends" type="line" :data="$workerAnalytics['attendance_trends'] ?? ['labels' => [], 'data' => []]"
                                    label="Attendance" :size="$workerAttendanceTrendSize" :expand="$workerVisibleCount === 1" :height="$workerStandardHeight"
                                    :body-min-height="$workerCardMinHeight" :reveal-delay="0" lazy="true" />
                            @endif
                            @if ($canShowChart('worker', 'active_vs_inactive'))
                                <x-analytics-card title="Active vs Inactive" type="doughnut" :data="$workerAnalytics['active_vs_inactive'] ?? ['labels' => [], 'data' => []]"
                                    label="Workers" :size="$workerActiveInactiveSize" :expand="$workerVisibleCount === 1" :height="$workerStandardHeight" :body-min-height="$workerCardMinHeight"
                                    :reveal-delay="80" lazy="true" />
                            @endif
                            @if ($canShowChart('worker', 'weekly_attendance'))
                                <x-analytics-card title="Weekly Attendance Trend" type="line" :data="$workerAnalytics['weekly_attendance'] ?? ['labels' => [], 'data' => []]"
                                    label="Attendance" :size="$workerWeeklyAttendanceSize" :expand="$workerVisibleCount === 1" :height="$workerFeaturedHeight"
                                    :body-min-height="$workerCardMinHeight" :reveal-delay="160" lazy="true" />
                            @endif
                        </div>
                    </section>
                @endif
            @endcanany
        </div>

        @includeIf('dashboard.partials.' . \Illuminate\Support\Str::slug($dashboard['active_role']))
    </div>
@endsection
