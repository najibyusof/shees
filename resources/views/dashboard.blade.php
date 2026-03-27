@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Dashboard" subtitle="Welcome back, {{ Auth::user()->name }}.">
        <x-slot:actions>
            @if (Auth::user()->hasPermissionTo('reports.view'))
                <a href="{{ route('audit.logs') }}"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">
                    Quick Audit View
                </a>
            @endif
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.card padding="p-4" class="bg-gradient-to-br from-slate-900 to-teal-800 text-white">
                <p class="text-xs font-semibold uppercase text-cyan-100">Incidents</p>
                <p class="mt-2 text-2xl font-bold">{{ number_format((int) ($stats['kpis']['incidents'] ?? 0)) }}</p>
            </x-ui.card>

            <x-ui.card padding="p-4">
                <p class="text-xs font-semibold uppercase ui-text-muted">Site Audits</p>
                <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($stats['kpis']['audits'] ?? 0)) }}</p>
            </x-ui.card>

            <x-ui.card padding="p-4">
                <p class="text-xs font-semibold uppercase ui-text-muted">Trainings</p>
                <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($stats['kpis']['trainings'] ?? 0)) }}</p>
            </x-ui.card>

            <x-ui.card padding="p-4">
                <p class="text-xs font-semibold uppercase ui-text-muted">Active Sessions</p>
                <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($stats['activeSessions'] ?? 0)) }}</p>
            </x-ui.card>
        </div>

        <x-ui.card title="Recent Activities" subtitle="Latest operational actions across modules.">
            @if (!empty($stats['recentActivity']))
                <div class="space-y-3">
                    @foreach ($stats['recentActivity'] as $event)
                        <div class="flex items-center justify-between gap-3 rounded-xl border ui-border ui-surface-soft px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold ui-text">{{ ucfirst(str_replace('_', ' ', $event['module'] ?? 'system')) }}</p>
                                <p class="text-xs ui-text-muted">{{ ucfirst(str_replace('_', ' ', $event['action'] ?? 'updated')) }}</p>
                            </div>
                            <p class="text-xs ui-text-muted">
                                {{ !empty($event['created_at']) ? \Illuminate\Support\Carbon::parse($event['created_at'])->diffForHumans() : 'N/A' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Recent Activities" description="Activity logs will appear here once users perform actions." />
            @endif
        </x-ui.card>

        @php
            $workerTracking = $stats['workerTracking'] ?? [];
            $workerWindow = $workerTracking['window'] ?? ['selected' => '30m', 'label' => '30 Min', 'options' => []];
            $workerLastUpdatedAt = $workerTracking['last_updated_at'] ?? null;
            $workerLastUpdated = $workerLastUpdatedAt ? \Illuminate\Support\Carbon::parse($workerLastUpdatedAt) : null;
            $siteAuditSummary = $stats['siteAuditSummary'] ?? [];
            $auditHealth = $stats['auditHealth'] ?? [];
            $cleanup = $auditHealth['cleanup'] ?? [];
            $cleanupLastRun = !empty($cleanup['last_run_at']) ? \Illuminate\Support\Carbon::parse($cleanup['last_run_at']) : null;
            $cleanupIsStale = !$cleanupLastRun || $cleanupLastRun->lt(now()->subDay());
        @endphp

        <x-ui.card title="Audit Log Health" subtitle="Daily activity volume, busiest modules, and cleanup signal.">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Audit Events Today</p>
                    <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($auditHealth['today_count'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl border ui-border p-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Top Modules Today</p>
                    @if (!empty($auditHealth['top_modules_today']))
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($auditHealth['top_modules_today'] as $module)
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-700 dark:text-gray-100">
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
                        Deleted: {{ number_format((int) ($cleanup['deleted_count'] ?? 0)) }} | Retention: {{ (int) ($cleanup['days'] ?? 180) }} days
                        @if (!empty($cleanup['dry_run']))
                            | Dry Run
                        @endif
                    </p>
                    @if ($cleanupIsStale)
                        <p class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
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
                    <p class="text-xs font-semibold uppercase ui-text-muted">On-Site Now ({{ $workerWindow['label'] }})</p>
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
                    <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($telemetry['jobs_pending'] ?? 0)) }}
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
