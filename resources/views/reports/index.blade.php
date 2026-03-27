@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Reports"
        subtitle="Filter, visualize, and export incidents, training, and audit records with saved presets.">
        <x-slot:actions>
            <x-ui.button :href="route('reports.builder')" variant="primary" size="md">Open Adhoc Builder</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    @php
        $statusBreakdown = $summary['status_breakdown'] ?? [];
        $trend = $summary['trend'] ?? [];
    @endphp

    <div class="space-y-6">
        <x-ui.card title="Saved Presets" subtitle="Reuse your frequent reporting filters instantly.">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($presets as $preset)
                    @php
                        $presetFilters = is_array($preset->filters) ? $preset->filters : [];
                        $presetQuery = [
                            'module' => $presetFilters['module'] ?? $preset->module,
                            'date_from' => $presetFilters['date_from'] ?? null,
                            'date_to' => $presetFilters['date_to'] ?? null,
                            'user_id' => $presetFilters['user_id'] ?? null,
                            'status' => $presetFilters['status'] ?? null,
                        ];
                    @endphp
                    <div class="rounded-xl border ui-border p-4">
                        <p class="text-sm font-semibold ui-text">{{ $preset->name }}</p>
                        <p class="mt-1 text-xs ui-text-muted">Module: {{ ucfirst($preset->module) }}</p>
                        <p class="mt-1 text-xs ui-text-muted">Export: {{ strtoupper($preset->export_format ?? 'csv') }}</p>
                        @if ($preset->schedule_enabled)
                            <p class="mt-1 text-xs ui-text-muted">
                                Schedule: {{ ucfirst($preset->schedule_frequency ?? 'daily') }} at
                                {{ $preset->schedule_time ?? '07:00' }}
                            </p>
                            <p class="mt-1 text-xs ui-text-muted">Next run:
                                {{ $preset->next_run_at?->format('Y-m-d H:i') ?? 'pending' }}</p>
                        @endif
                        <div class="mt-3 flex items-center gap-2">
                            <x-ui.button :href="route('reports.index', array_filter($presetQuery, fn($value) => filled($value)))" variant="secondary" size="sm">Apply</x-ui.button>
                            <form method="POST" action="{{ route('reports.presets.run', $preset) }}">
                                @csrf
                                <x-ui.button type="submit" variant="primary" size="sm">Run Now</x-ui.button>
                            </form>
                            <form method="POST" action="{{ route('reports.presets.destroy', $preset) }}"
                                onsubmit="return confirm('Delete this preset?');">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm">Delete</x-ui.button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm ui-text-muted">No saved presets yet. Save one from your current filters below.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Report Filters" subtitle="Filter by module, date range, user, and status.">
            <form method="GET" action="{{ route('reports.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div>
                    <label for="module" class="mb-1.5 block text-sm font-medium ui-text">Module</label>
                    <select id="module" name="module"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="incidents" @selected(($filters['module'] ?? 'incidents') === 'incidents')>Incidents</option>
                        <option value="trainings" @selected(($filters['module'] ?? '') === 'trainings')>Training</option>
                        <option value="audits" @selected(($filters['module'] ?? '') === 'audits')>Audits</option>
                    </select>
                </div>

                <div>
                    <label for="date_from" class="mb-1.5 block text-sm font-medium ui-text">Date From</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                </div>

                <div>
                    <label for="date_to" class="mb-1.5 block text-sm font-medium ui-text">Date To</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                </div>

                <div>
                    <label for="user_id" class="mb-1.5 block text-sm font-medium ui-text">User</label>
                    <select id="user_id" name="user_id"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="">All Users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) ($filters['user_id'] ?? 0) === (int) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="mb-1.5 block text-sm font-medium ui-text">Status</label>
                    <select id="status" name="status"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="">All Statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                {{ str_replace('_', ' ', ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 xl:col-span-5 flex flex-wrap items-center justify-between gap-2">
                    <div class="inline-flex gap-2">
                        <x-ui.button type="submit" variant="primary" size="sm">Apply Filters</x-ui.button>
                        <x-ui.button :href="route('reports.index')" variant="secondary" size="sm">Reset</x-ui.button>
                    </div>

                    <div class="inline-flex gap-2">
                        <x-ui.button :href="route('reports.export', array_merge($filters, ['format' => 'csv']))" variant="secondary" size="sm">Export CSV</x-ui.button>
                        <x-ui.button :href="route('reports.export', array_merge($filters, ['format' => 'pdf']))" variant="secondary" size="sm">Export PDF</x-ui.button>
                        <x-ui.button :href="route('reports.export', array_merge($filters, ['format' => 'csv', 'async' => 1]))" variant="ghost" size="sm">Queue CSV</x-ui.button>
                        <x-ui.button :href="route('reports.export', array_merge($filters, ['format' => 'pdf', 'async' => 1]))" variant="ghost" size="sm">Queue PDF</x-ui.button>
                    </div>
                </div>
            </form>

            <div class="mt-4 rounded-xl border ui-border p-4">
                <p class="mb-2 text-xs font-semibold uppercase ui-text-muted">Save Current Filters as Preset</p>
                <form method="POST" action="{{ route('reports.presets.store') }}"
                    class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="hidden" name="module" value="{{ $filters['module'] }}">
                    <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
                    <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">
                    <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    <input type="text" name="name" placeholder="Preset name"
                        class="w-full max-w-xs rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text"
                        required>
                    <select name="export_format"
                        class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="csv">CSV</option>
                        <option value="pdf">PDF</option>
                    </select>
                    <label class="inline-flex items-center gap-1 text-sm ui-text-muted">
                        <input type="checkbox" name="schedule_enabled" value="1" class="rounded border ui-border">
                        Schedule
                    </label>
                    <select name="schedule_frequency"
                        class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                    </select>
                    <input type="time" name="schedule_time" value="07:00"
                        class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                    <x-ui.button type="submit" variant="secondary" size="sm">Save Preset</x-ui.button>
                </form>
            </div>
        </x-ui.card>

        <x-ui.card title="Report Summary" subtitle="Status mix and 14-day activity trend for current filters.">
            <div class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Status Breakdown</p>
                    <div class="mt-3 space-y-2">
                        @foreach ($statusBreakdown as $point)
                            <div
                                class="flex items-center justify-between rounded-lg border ui-border ui-surface-soft px-3 py-2 text-xs">
                                <span class="ui-text-muted">{{ str_replace('_', ' ', ucfirst($point['label'])) }}</span>
                                <span class="font-semibold ui-text">{{ number_format((int) $point['count']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border ui-border p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">14-Day Trend</p>
                    <x-ui.table class="mt-4" empty="No trend records for current filters.">
                        <x-slot name="head">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Count</th>
                            </tr>
                        </x-slot>

                        @foreach ($trend as $point)
                            <tr>
                                <td class="px-4 py-3 ui-text-muted">{{ $point['label'] }}</td>
                                <td class="px-4 py-3 font-semibold ui-text">{{ number_format((int) $point['count']) }}
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Recent Export Jobs" subtitle="Track async export status and download completed files.">
            @if ($recentExports->count() > 0)
                <x-ui.table>
                    <x-slot name="head">
                        <tr>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3">Module</th>
                            <th class="px-4 py-3">Format</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Completed</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </x-slot>

                    @foreach ($recentExports as $export)
                        <tr>
                            <td class="px-4 py-3">{{ $export->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">{{ ucfirst($export->module) }}</td>
                            <td class="px-4 py-3">{{ strtoupper($export->format) }}</td>
                            <td class="px-4 py-3">{{ $export->status }}</td>
                            <td class="px-4 py-3">{{ $export->completed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($export->status === 'completed')
                                    <x-ui.button :href="route('reports.exports.download', $export)" variant="secondary"
                                        size="sm">Download</x-ui.button>
                                @elseif($export->status === 'failed')
                                    <span class="text-xs text-rose-600">{{ $export->error_message ?: 'Failed' }}</span>
                                @else
                                    <span class="text-xs ui-text-muted">Processing</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @else
                <x-ui.empty-state title="No Export Jobs"
                    description="Queue an export to generate larger files in the background." />
            @endif
        </x-ui.card>

        <x-ui.card :title="$moduleLabel . ' Report'" subtitle="Paginated results optimized for large data sets.">
            @if ($rows->count() > 0)
                <x-ui.table>
                    @if (($filters['module'] ?? 'incidents') === 'incidents')
                        <x-slot name="head">
                            <tr>
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Classification</th>
                                <th class="px-4 py-3">Location</th>
                                <th class="px-4 py-3">Date/Time</th>
                                <th class="px-4 py-3">User</th>
                            </tr>
                        </x-slot>
                        @foreach ($rows as $incident)
                            <tr>
                                <td class="px-4 py-3">{{ $incident->id }}</td>
                                <td class="px-4 py-3 font-medium ui-text">{{ $incident->title }}</td>
                                <td class="px-4 py-3">{{ $incident->status }}</td>
                                <td class="px-4 py-3">{{ $incident->classification }}</td>
                                <td class="px-4 py-3">{{ $incident->location }}</td>
                                <td class="px-4 py-3">{{ optional($incident->datetime)->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $incident->reporter?->name ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @elseif(($filters['module'] ?? '') === 'trainings')
                        <x-slot name="head">
                            <tr>
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Start</th>
                                <th class="px-4 py-3">End</th>
                                <th class="px-4 py-3">Users</th>
                                <th class="px-4 py-3">Certificates</th>
                            </tr>
                        </x-slot>
                        @foreach ($rows as $training)
                            <tr>
                                <td class="px-4 py-3">{{ $training->id }}</td>
                                <td class="px-4 py-3 font-medium ui-text">{{ $training->titleForLocale() }}</td>
                                <td class="px-4 py-3">{{ $training->is_active ? 'active' : 'inactive' }}</td>
                                <td class="px-4 py-3">{{ optional($training->starts_at)->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ optional($training->ends_at)->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $training->users_count }}</td>
                                <td class="px-4 py-3">{{ $training->certificates_count }}</td>
                            </tr>
                        @endforeach
                    @else
                        <x-slot name="head">
                            <tr>
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Site</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Scheduled</th>
                                <th class="px-4 py-3">KPI</th>
                                <th class="px-4 py-3">NCR</th>
                                <th class="px-4 py-3">User</th>
                            </tr>
                        </x-slot>
                        @foreach ($rows as $audit)
                            <tr>
                                <td class="px-4 py-3">{{ $audit->id }}</td>
                                <td class="px-4 py-3 font-medium ui-text">{{ $audit->reference_no }}</td>
                                <td class="px-4 py-3">{{ $audit->site_name }}</td>
                                <td class="px-4 py-3">{{ $audit->status }}</td>
                                <td class="px-4 py-3">{{ optional($audit->scheduled_for)->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    {{ $audit->kpi_score !== null ? number_format($audit->kpi_score, 1) . '%' : '-' }}</td>
                                <td class="px-4 py-3">{{ $audit->ncr_reports_count }}</td>
                                <td class="px-4 py-3">{{ $audit->creator?->name ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @endif
                </x-ui.table>

                <div class="mt-4">
                    <x-ui.pagination :paginator="$rows" />
                </div>
            @else
                <x-ui.empty-state title="No Report Data"
                    description="No rows match your current filters. Adjust date/user/status and try again." />
            @endif
        </x-ui.card>
    </div>
@endsection
