@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Inspections" subtitle="Execute inspections and track offline sync lifecycle.">
        <x-slot:actions>
            <x-ui.button :href="route('inspections.create')" variant="primary" size="md">Start Inspection</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card title="Inspection Runs">
        @if ($inspections->count() > 0)
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3">Checklist</th>
                        <th class="px-4 py-3">Inspector</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Sync</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </x-slot:head>
                @foreach ($inspections as $inspection)
                    <tr>
                        <td class="px-4 py-3 ui-text">{{ $inspection->checklist?->titleForLocale() ?? '-' }}</td>
                        <td class="px-4 py-3 ui-text-muted">{{ $inspection->inspector?->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.status-badge :status="$inspection->status === 'completed' ? 'under_review' : $inspection->status" />
                        </td>
                        <td class="px-4 py-3 ui-text-muted">{{ $inspection->location ?? '-' }}</td>
                        <td class="px-4 py-3 ui-text-muted">{{ $inspection->sync_status }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button :href="route('inspections.show', $inspection)" variant="ghost" size="sm">Open</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-4">
                <x-ui.pagination :paginator="$inspections" />
            </div>
        @else
            <x-ui.empty-state title="No Inspections"
                description="Start your first inspection run from an active checklist." />
        @endif
    </x-ui.card>
@endsection
