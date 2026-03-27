@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="$worker->full_name" subtitle="Attendance history, geofence events, and worker profile context.">
        <x-slot:actions>
            <x-ui.button :href="route('worker-tracking.ui.index')" variant="secondary" size="md">Back to Workers</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <x-ui.card title="Worker Profile">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs uppercase ui-text-muted">Employee Code</p>
                    <p class="mt-1 text-sm ui-text">{{ $worker->employee_code }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Department</p>
                    <p class="mt-1 text-sm ui-text">{{ $worker->department ?: 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Position</p>
                    <p class="mt-1 text-sm ui-text">{{ $worker->position ?: 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Status</p>
                    <div class="mt-1">
                        <x-ui.badge :variant="match ($worker->status) {
                            'active' => 'success',
                            'on-leave' => 'warning',
                            default => 'neutral',
                        }">{{ ucfirst($worker->status) }}</x-ui.badge>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Attendance Timeline" subtitle="Latest location and geofence events.">
            @if ($attendanceLogs->count() > 0)
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th class="px-4 py-3">Logged At</th>
                            <th class="px-4 py-3">Event Type</th>
                            <th class="px-4 py-3">Location</th>
                            <th class="px-4 py-3">Geofence</th>
                            <th class="px-4 py-3">Alert</th>
                            <th class="px-4 py-3">Source</th>
                        </tr>
                    </x-slot:head>

                    @foreach ($attendanceLogs as $log)
                        <tr>
                            <td class="px-4 py-3 ui-text-muted">{{ $log->logged_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</td>
                            <td class="px-4 py-3 ui-text">{{ ucfirst(str_replace('_', ' ', $log->event_type ?? 'ping')) }}</td>
                            <td class="px-4 py-3 ui-text-muted">
                                {{ $log->latitude ? number_format((float) $log->latitude, 5) : '-' }},
                                {{ $log->longitude ? number_format((float) $log->longitude, 5) : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$log->inside_geofence ? 'success' : 'error'">
                                    {{ $log->inside_geofence ? 'Inside' : 'Outside' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                @if ($log->alert_level)
                                    <x-ui.badge :variant="match ($log->alert_level) {
                                        'high' => 'error',
                                        'medium' => 'warning',
                                        default => 'info',
                                    }">{{ ucfirst($log->alert_level) }}</x-ui.badge>
                                @else
                                    <span class="text-xs ui-text-muted">None</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 ui-text-muted">{{ strtoupper((string) $log->source) }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="mt-4">
                    <x-ui.pagination :paginator="$attendanceLogs" />
                </div>
            @else
                <x-ui.empty-state title="No Attendance Logs" description="No tracking data has been recorded for this worker yet." />
            @endif
        </x-ui.card>
    </div>
@endsection
