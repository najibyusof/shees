@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Inspection Checklists" subtitle="Build reusable checklist templates for inspections.">
        <x-slot:actions>
            @can('create', \App\Models\InspectionChecklist::class)
                <x-ui.button :href="route('inspection-checklists.create')" variant="primary" size="md">Create Checklist</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <x-ui.card title="Checklist Templates">
        @if ($checklists->count() > 0)
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Version</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3">Inspections</th>
                        <th class="px-4 py-3">Sync</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </x-slot:head>

                @foreach ($checklists as $checklist)
                    <tr>
                        <td class="px-4 py-3 font-medium ui-text">{{ $checklist->titleForLocale() }}</td>
                        <td class="px-4 py-3 ui-text-muted">v{{ $checklist->version }}</td>
                        <td class="px-4 py-3 ui-text-muted">{{ $checklist->items_count }}</td>
                        <td class="px-4 py-3 ui-text-muted">{{ $checklist->inspections_count }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="match ($checklist->sync_status) {
                                'synced' => 'success',
                                'pending_sync' => 'warning',
                                default => 'error',
                            }">
                                {{ $checklist->sync_status }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                @can('view', $checklist)
                                    <x-ui.button :href="route('inspection-checklists.show', $checklist)" variant="ghost" size="sm">View</x-ui.button>
                                @endcan
                                @can('update', $checklist)
                                    <x-ui.button :href="route('inspection-checklists.edit', $checklist)" variant="secondary" size="sm">Edit</x-ui.button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="mt-4">
                <x-ui.pagination :paginator="$checklists" />
            </div>
        @else
            <x-ui.empty-state title="No Checklists Yet"
                description="Create a checklist template to begin running inspections." />
        @endif
    </x-ui.card>
@endsection
