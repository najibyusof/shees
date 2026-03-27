@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Worker Tracking" subtitle="Monitor workforce status, attendance activity, and geofence alerts." />
@endsection

@section('content')
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.card padding="p-4">
                <p class="text-xs font-semibold uppercase ui-text-muted">Total Workers</p>
                <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($summary['total_workers'] ?? 0)) }}</p>
            </x-ui.card>
            <x-ui.card padding="p-4">
                <p class="text-xs font-semibold uppercase ui-text-muted">Active Workers</p>
                <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($summary['active_workers'] ?? 0)) }}</p>
            </x-ui.card>
            <x-ui.card padding="p-4">
                <p class="text-xs font-semibold uppercase ui-text-muted">On Leave</p>
                <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($summary['on_leave_workers'] ?? 0)) }}</p>
            </x-ui.card>
            <x-ui.card padding="p-4">
                <p class="text-xs font-semibold uppercase ui-text-muted">Geofence Alerts (24h)</p>
                <p class="mt-2 text-2xl font-bold ui-text">{{ number_format((int) ($summary['alerts_24h'] ?? 0)) }}</p>
            </x-ui.card>
        </div>

        <x-ui.card title="Worker List" subtitle="Current worker roster with attendance and geofence signals.">
            @if ($workers->count() > 0)
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th class="px-4 py-3">Worker</th>
                            <th class="px-4 py-3">Employee Code</th>
                            <th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Attendance Today</th>
                            <th class="px-4 py-3">Alerts (24h)</th>
                            <th class="px-4 py-3">Last Seen</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </x-slot:head>

                    @foreach ($workers as $worker)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium ui-text">{{ $worker->full_name }}</p>
                                <p class="text-xs ui-text-muted">{{ $worker->user?->email ?? 'No linked user' }}</p>
                            </td>
                            <td class="px-4 py-3 ui-text-muted">{{ $worker->employee_code }}</td>
                            <td class="px-4 py-3 ui-text-muted">{{ $worker->department ?: 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="match ($worker->status) {
                                    'active' => 'success',
                                    'on-leave' => 'warning',
                                    default => 'neutral',
                                }">{{ ucfirst($worker->status) }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 ui-text">{{ number_format((int) $worker->attendance_today_count) }}</td>
                            <td class="px-4 py-3 ui-text">{{ number_format((int) $worker->geofence_alerts_count) }}</td>
                            <td class="px-4 py-3 ui-text-muted">{{ $worker->last_seen_at?->diffForHumans() ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.button :href="route('worker-tracking.ui.show', $worker)" variant="ghost" size="sm">View</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="mt-4">
                    <x-ui.pagination :paginator="$workers" />
                </div>
            @else
                <x-ui.empty-state title="No Workers"
                    description="Worker records will appear here once they are added to tracking." />
            @endif
        </x-ui.card>
    </div>
@endsection
