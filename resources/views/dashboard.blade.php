@extends('layouts.app')

@section('header')
    <div
        class="rounded-2xl bg-gradient-to-r from-teal-700 via-teal-700 to-cyan-700 px-5 py-4 text-white shadow-lg shadow-teal-900/15 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Welcome back, {{ Auth::user()->name }}!</h1>
                <p class="mt-1 text-sm text-teal-100">Here's your safety performance summary for today -
                    {{ now()->format('l, d M Y') }}</p>
            </div>

            @if (Auth::user()->hasPermissionTo('reports.view'))
                <a href="{{ route('audit.logs') }}"
                    class="rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/20">
                    Quick Audit View
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.card padding="p-4" class="border-l-4 border-l-orange-400">
                <p class="text-xs font-semibold uppercase ui-text-muted">Open Incidents</p>
                <p class="mt-2 text-3xl font-bold ui-text">{{ number_format((int) ($stats['kpis']['incidents'] ?? 0)) }}</p>
            </x-ui.card>

            <x-ui.card padding="p-4" class="border-l-4 border-l-rose-400">
                <p class="text-xs font-semibold uppercase ui-text-muted">Active Site Audits</p>
                <p class="mt-2 text-3xl font-bold ui-text">{{ number_format((int) ($stats['kpis']['audits'] ?? 0)) }}</p>
            </x-ui.card>

            <x-ui.card padding="p-4" class="border-l-4 border-l-violet-400">
                <p class="text-xs font-semibold uppercase ui-text-muted">Emergency Cases</p>
                <p class="mt-2 text-3xl font-bold ui-text">{{ number_format((int) ($stats['kpis']['trainings'] ?? 0)) }}</p>
            </x-ui.card>

            <x-ui.card padding="p-4" class="border-l-4 border-l-sky-400">
                <p class="text-xs font-semibold uppercase ui-text-muted">Risk Assessments</p>
                <p class="mt-2 text-3xl font-bold ui-text">{{ number_format((int) ($stats['activeSessions'] ?? 0)) }}</p>
            </x-ui.card>
        </div>

        @php
            $incidentTelemetry = $stats['incidentTelemetry'] ?? [];
            $incidentTrend = $incidentTelemetry['trend'] ?? [];
            $incidentPointCount = max(count($incidentTrend) - 1, 1);
            $incidentMax = max(1, (int) ($incidentTelemetry['peak'] ?? 0));
            $chartWidth = 100;
            $chartHeight = 42;

            $createdLinePoints = collect($incidentTrend)
                ->values()
                ->map(function (array $point, int $index) use (
                    $incidentPointCount,
                    $incidentMax,
                    $chartWidth,
                    $chartHeight,
                ) {
                    $x = ($index / $incidentPointCount) * $chartWidth;
                    $y = $chartHeight - ((int) $point['count'] / $incidentMax) * $chartHeight;
                    return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                })
                ->implode(' ');

            $resolvedMax = max(1, (int) collect($incidentTrend)->max('resolved'));
            $unresolvedMax = max(1, (int) collect($incidentTrend)->max('unresolved'));
            $statusMax = max($resolvedMax, $unresolvedMax);

            $resolvedLinePoints = collect($incidentTrend)
                ->values()
                ->map(function (array $point, int $index) use (
                    $incidentPointCount,
                    $statusMax,
                    $chartWidth,
                    $chartHeight,
                ) {
                    $x = ($index / $incidentPointCount) * $chartWidth;
                    $y = $chartHeight - ((int) ($point['resolved'] ?? 0) / $statusMax) * $chartHeight;
                    return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                })
                ->implode(' ');

            $unresolvedLinePoints = collect($incidentTrend)
                ->values()
                ->map(function (array $point, int $index) use (
                    $incidentPointCount,
                    $statusMax,
                    $chartWidth,
                    $chartHeight,
                ) {
                    $x = ($index / $incidentPointCount) * $chartWidth;
                    $y = $chartHeight - ((int) ($point['unresolved'] ?? 0) / $statusMax) * $chartHeight;
                    return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                })
                ->implode(' ');

            $firstPoint = collect($incidentTrend)->first();
            $lastPoint = collect($incidentTrend)->last();
            $firstY = $firstPoint
                ? $chartHeight - ((int) $firstPoint['count'] / $incidentMax) * $chartHeight
                : $chartHeight;
            $lastY = $lastPoint
                ? $chartHeight - ((int) $lastPoint['count'] / $incidentMax) * $chartHeight
                : $chartHeight;
            $areaPath =
                'M0,' .
                number_format($firstY, 2, '.', '') .
                ' L' .
                $createdLinePoints .
                ' L' .
                $chartWidth .
                ',' .
                $chartHeight .
                ' L0,' .
                $chartHeight .
                ' Z';

            $classificationMix = $incidentTelemetry['classification_mix'] ?? [];
            $classificationMax = max(1, (int) collect($classificationMix)->max('total'));

            $closedMonthly = $incidentTelemetry['closed_monthly'] ?? [];
            $closedMax = max(1, (int) collect($closedMonthly)->max('count'));
            $incidentSummary = $incidentTelemetry['summary'] ?? [];
        @endphp

        <x-ui.card title="Incident Analytics" subtitle="Resolution velocity, status trend, categories, and closure volume.">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Open</p>
                    <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($incidentSummary['open'] ?? 0)) }}
                    </p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Resolved</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($incidentSummary['resolved'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Resolution Rate</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((float) ($incidentSummary['resolution_rate'] ?? 0), 1) }}%</p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Avg Time to Resolve</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((float) ($incidentSummary['avg_resolution_hours'] ?? 0), 1) }}h</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border ui-border p-4">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase ui-text-muted">Resolved vs Unresolved</p>
                        <p class="text-xs ui-text-muted">{{ $incidentTelemetry['window_label'] ?? 'Last 30 Days' }}</p>
                    </div>

                    @if (count($incidentTrend) > 0)
                        <div class="h-44 w-full rounded-lg ui-surface-soft p-3">
                            <svg viewBox="0 0 100 42" preserveAspectRatio="none" class="h-full w-full">
                                <polyline points="{{ $resolvedLinePoints }}" fill="none" stroke="#38bdf8"
                                    stroke-width="1.1" />
                                <polyline points="{{ $unresolvedLinePoints }}" fill="none" stroke="#16a34a"
                                    stroke-width="1.1" />
                            </svg>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs ui-text-muted">
                            <span>{{ $incidentTrend[0]['label'] ?? '' }}</span>
                            <span>{{ $incidentTrend[count($incidentTrend) - 1]['label'] ?? '' }}</span>
                        </div>

                        <div class="mt-3 flex items-center gap-4 text-xs ui-text-muted">
                            <span class="inline-flex items-center gap-1"><span
                                    class="h-2.5 w-2.5 rounded-full bg-sky-400"></span>Resolved</span>
                            <span class="inline-flex items-center gap-1"><span
                                    class="h-2.5 w-2.5 rounded-full bg-green-500"></span>Unresolved</span>
                        </div>
                    @else
                        <x-ui.empty-state title="No Status Trend"
                            description="Trend appears once incident records are available." />
                    @endif
                </div>

                <div class="rounded-xl border ui-border p-4">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase ui-text-muted">Incidents by Classification</p>
                        <p class="text-xs ui-text-muted">Current distribution</p>
                    </div>

                    @if (count($classificationMix) > 0)
                        <div class="space-y-3 pt-2">
                            @foreach ($classificationMix as $classification)
                                @php
                                    $width = min(100, ((int) $classification['total'] / $classificationMax) * 100);
                                @endphp
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-xs ui-text-muted">
                                        <span>{{ $classification['classification'] }}</span>
                                        <span>{{ number_format((int) $classification['total']) }}</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full ui-surface-soft">
                                        <div class="h-2 rounded-full bg-emerald-500"
                                            style="width: {{ number_format($width, 2, '.', '') }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state title="No Category Data"
                            description="Classification bars appear once incidents are categorized." />
                    @endif
                </div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-5">
                <div class="rounded-xl border ui-border p-4 lg:col-span-3">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase ui-text-muted">Incident Created Trend</p>
                        <p class="text-xs ui-text-muted">{{ $incidentTelemetry['window_label'] ?? 'Last 30 Days' }}</p>
                    </div>

                    @if (count($incidentTrend) > 0)
                        <div class="h-36 w-full rounded-lg ui-surface-soft p-3">
                            <svg viewBox="0 0 100 42" preserveAspectRatio="none" class="h-full w-full">
                                <defs>
                                    <linearGradient id="incidentAreaGradient" x1="0" y1="0" x2="0"
                                        y2="1">
                                        <stop offset="0%" stop-color="#0f766e" stop-opacity="0.35" />
                                        <stop offset="100%" stop-color="#0f766e" stop-opacity="0.04" />
                                    </linearGradient>
                                </defs>
                                <path d="{{ $areaPath }}" fill="url(#incidentAreaGradient)" />
                                <polyline points="{{ $createdLinePoints }}" fill="none" stroke="#0f766e"
                                    stroke-width="1.1" />
                            </svg>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs ui-text-muted">
                            <span>{{ $incidentTrend[0]['label'] ?? '' }}</span>
                            <span>Total: {{ number_format((int) ($incidentTelemetry['total'] ?? 0)) }} | Peak:
                                {{ number_format((int) ($incidentTelemetry['peak'] ?? 0)) }}</span>
                            <span>{{ $incidentTrend[count($incidentTrend) - 1]['label'] ?? '' }}</span>
                        </div>
                    @else
                        <x-ui.empty-state title="No Incident Trend"
                            description="Incident graph will appear once incident records are available." />
                    @endif
                </div>

                <div class="rounded-xl border ui-border p-4 lg:col-span-2">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase ui-text-muted">Closed Incidents</p>
                        <p class="text-xs ui-text-muted">
                            {{ $incidentTelemetry['closed_window_label'] ?? 'Last 6 Months' }}</p>
                    </div>

                    @if (count($closedMonthly) > 0)
                        <div class="mt-2 flex h-36 items-end gap-3 rounded-lg ui-surface-soft p-3">
                            @foreach ($closedMonthly as $month)
                                @php
                                    $barHeight = ((int) $month['count'] / $closedMax) * 100;
                                @endphp
                                <div class="flex flex-1 flex-col items-center justify-end gap-2">
                                    <span
                                        class="text-[11px] font-semibold ui-text-muted">{{ number_format((int) $month['count']) }}</span>
                                    <div class="w-full rounded-md bg-sky-500/85"
                                        style="height: {{ max(8, (int) round($barHeight)) }}%"></div>
                                    <span class="text-[11px] ui-text-muted">{{ $month['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state title="No Closure Data"
                            description="Monthly closed incident bars appear when closure events exist." />
                    @endif
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($incidentTelemetry['status_mix'] ?? [] as $status)
                    <span
                        class="rounded-full border ui-border ui-surface-soft px-3 py-1 text-xs font-semibold ui-text-muted">
                        {{ $status['label'] }}: {{ number_format((int) $status['total']) }}
                    </span>
                @endforeach
            </div>
        </x-ui.card>

        @php
            $workerTracking = $stats['workerTracking'] ?? [];
            $workerWindow = $workerTracking['window'] ?? ['selected' => '30m', 'label' => '30 Min', 'options' => []];
            $workerLastUpdatedAt = $workerTracking['last_updated_at'] ?? null;
            $workerLastUpdated = $workerLastUpdatedAt ? \Illuminate\Support\Carbon::parse($workerLastUpdatedAt) : null;
            $siteAuditSummary = $stats['siteAuditSummary'] ?? [];
            $auditHealth = $stats['auditHealth'] ?? [];
            $cleanup = $auditHealth['cleanup'] ?? [];
            $cleanupLastRun = !empty($cleanup['last_run_at'])
                ? \Illuminate\Support\Carbon::parse($cleanup['last_run_at'])
                : null;
            $cleanupIsStale = !$cleanupLastRun || $cleanupLastRun->lt(now()->subDay());
        @endphp

        <x-ui.card title="Audit Log Health" subtitle="Daily activity volume, busiest modules, and cleanup signal.">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Audit Events Today</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($auditHealth['today_count'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Top Modules Today</p>
                    @if (!empty($auditHealth['top_modules_today']))
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($auditHealth['top_modules_today'] as $module)
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-700 dark:text-gray-100">
                                    {{ $module['module'] }}: {{ number_format((int) ($module['total'] ?? 0)) }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-2 text-sm ui-text-muted">No audit activity recorded yet today.</p>
                    @endif
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Cleanup Last Run</p>
                    <p class="mt-2 text-sm font-semibold ui-text">
                        {{ $cleanupLastRun ? $cleanupLastRun->diffForHumans() : 'Never' }}
                    </p>
                    <p class="mt-1 text-xs ui-text-muted">
                        {{ $cleanupLastRun ? $cleanupLastRun->format('Y-m-d H:i:s') : 'No run status yet' }}
                    </p>
                    <p class="mt-2 text-xs ui-text-muted">
                        Deleted: {{ number_format((int) ($cleanup['deleted_count'] ?? 0)) }} | Retention:
                        {{ (int) ($cleanup['days'] ?? 180) }} days
                        @if (!empty($cleanup['dry_run']))
                            | Dry Run
                        @endif
                    </p>
                    @if ($cleanupIsStale)
                        <p
                            class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                            Cleanup status is stale (older than 24h). Consider running the cleanup command.
                        </p>
                    @endif
                </div>
            </div>

            <x-slot:footer>
                <div class="flex justify-end">
                    <x-ui.button :href="route('audit.logs')" variant="secondary" size="sm">Open Audit Logs</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.card>

        <x-ui.card title="Worker Tracking" subtitle="Live workforce presence and recent geofence exceptions.">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold uppercase ui-text-muted">On-Site Window</span>
                    @foreach ($workerWindow['options'] as $option)
                        @php
                            $isSelected = $workerWindow['selected'] === $option['key'];
                        @endphp
                        <a href="{{ route('dashboard', array_merge(request()->query(), ['worker_window' => $option['key']])) }}"
                            class="rounded-full border px-3 py-1 text-xs font-semibold transition {{ $isSelected ? 'border-emerald-500 bg-emerald-500 text-white' : 'ui-border ui-text-muted hover:ui-surface-soft' }}">
                            {{ $option['label'] }}
                        </a>
                    @endforeach
                </div>

                <p class="text-xs ui-text-muted">
                    Last updated
                    {{ $workerLastUpdated ? $workerLastUpdated->diffForHumans() . ' | ' . $workerLastUpdated->format('Y-m-d H:i:s') : 'N/A' }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Active Workers</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($workerTracking['active_workers'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">On-Site Now ({{ $workerWindow['label'] }})
                    </p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($workerTracking['on_site_now'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Out-of-Geofence (24h)</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($workerTracking['alerts_last_24h'] ?? 0)) }}</p>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex justify-end">
                    <x-ui.button :href="route('worker-tracking.ui.index')" variant="secondary" size="sm">Open Worker Tracking</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.card>

        <x-ui.card title="Site Performance & Audit"
            subtitle="Audit scheduling, KPI performance, NCR exposure, and corrective action backlog.">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Scheduled</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($siteAuditSummary['scheduled'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">In Review</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($siteAuditSummary['in_review'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Approved</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($siteAuditSummary['approved'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Open NCR</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($siteAuditSummary['open_ncr'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Overdue Actions</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($siteAuditSummary['overdue_actions'] ?? 0)) }}</p>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex justify-end">
                    <x-ui.button :href="route('site-audits.index')" variant="secondary" size="sm">Open Site Audits</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.card>

        <x-ui.card title="Inspection Sync Telemetry" subtitle="Live throughput and latency from mobile sync processing.">
            @php
                $telemetry = $stats['syncTelemetry'] ?? [];
                $window = $telemetry['window'] ?? ['selected' => '7d', 'label' => '7 Days', 'options' => []];
                $trend = $telemetry['trend'] ?? [];
            @endphp

            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold uppercase ui-text-muted">Window</span>
                @foreach ($window['options'] as $option)
                    @php
                        $isSelected = $window['selected'] === $option['key'];
                    @endphp
                    <a href="{{ route('dashboard', array_merge(request()->query(), ['sync_window' => $option['key']])) }}"
                        class="rounded-full border px-3 py-1 text-xs font-semibold transition {{ $isSelected ? 'border-sky-500 bg-sky-500 text-white' : 'ui-border ui-text-muted hover:ui-surface-soft' }}">
                        {{ $option['label'] }}
                    </a>
                @endforeach
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Total Jobs</p>
                    <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($telemetry['jobs_total'] ?? 0)) }}
                    </p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Pending</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($telemetry['jobs_pending'] ?? 0)) }}
                    </p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Conflicts</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($telemetry['jobs_conflict'] ?? 0)) }}
                    </p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Open Conflicts</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ number_format((int) ($telemetry['open_conflicts'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Avg Latency</p>
                    <p class="mt-2 text-2xl font-bold ui-text">
                        {{ $telemetry['avg_latency_ms'] !== null ? number_format((float) $telemetry['avg_latency_ms'], 1) . ' ms' : 'N/A' }}
                    </p>
                </div>
            </div>

            <div class="mt-5 rounded-xl border ui-border p-4">
                <p class="text-xs font-semibold uppercase ui-text-muted">{{ $window['label'] }} Job Trend</p>
                <x-ui.table class="mt-4" empty="No trend records for selected period.">
                    <x-slot name="head">
                        <tr>
                            <th class="px-4 py-3">Period</th>
                            <th class="px-4 py-3">Jobs</th>
                            <th class="px-4 py-3">Avg Latency</th>
                        </tr>
                    </x-slot>

                    @foreach ($trend as $point)
                        <tr>
                            <td class="px-4 py-3 ui-text-muted">{{ $point['label'] }}</td>
                            <td class="px-4 py-3 font-semibold ui-text">{{ number_format((int) $point['jobs']) }}</td>
                            <td class="px-4 py-3 ui-text-muted">
                                {{ $point['avg_latency_ms'] !== null ? number_format((float) $point['avg_latency_ms'], 1) . ' ms' : 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </div>

            <div class="mt-5 rounded-xl border ui-border p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Top Failing Devices ({{ $window['label'] }})
                    </p>
                    <p class="text-xs ui-text-muted">Conflict + failed sync jobs</p>
                </div>

                @php
                    $failingDevices = $telemetry['top_failing_devices'] ?? [];
                @endphp

                @if (count($failingDevices) > 0)
                    <x-ui.table class="mt-4" empty="No failing devices recorded in the last 7 days.">
                        <x-slot name="head">
                            <tr>
                                <th class="px-4 py-3">Device</th>
                                <th class="px-4 py-3">Failures</th>
                                <th class="px-4 py-3">Conflicts</th>
                                <th class="px-4 py-3">Failed</th>
                                <th class="px-4 py-3">Last Seen</th>
                            </tr>
                        </x-slot>

                        @foreach ($failingDevices as $device)
                            <tr>
                                <td class="px-4 py-3 font-medium ui-text">{{ $device['device'] }}</td>
                                <td class="px-4 py-3 ui-text">{{ number_format($device['failures']) }}</td>
                                <td class="px-4 py-3 ui-text">{{ number_format($device['conflicts']) }}</td>
                                <td class="px-4 py-3 ui-text">{{ number_format($device['failed']) }}</td>
                                <td class="px-4 py-3 ui-text-muted">
                                    {{ $device['last_seen_at'] ? \Illuminate\Support\Carbon::parse($device['last_seen_at'])->diffForHumans() : 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @else
                    <p class="mt-4 text-sm ui-text-muted">No failing devices recorded in the last 7 days.</p>
                @endif
            </div>
        </x-ui.card>

        <x-ui.alert type="info" title="System Status">
            Role-based access control is active. Menu items and actions are shown based on your role and permissions.
        </x-ui.alert>
    </div>
@endsection
