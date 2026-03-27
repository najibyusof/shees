@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="$checklist->titleForLocale()" subtitle="Checklist template details and builder items.">
        <x-slot:actions>
            <x-ui.button :href="route('inspection-checklists.index')" variant="secondary" size="md">Back</x-ui.button>
            <x-ui.button :href="route('inspection-checklists.edit', $checklist)" variant="primary" size="md">Edit</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <x-ui.card title="Template Overview">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs uppercase ui-text-muted">Code</p>
                    <p class="mt-1 text-sm ui-text">{{ $checklist->code ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Version</p>
                    <p class="mt-1 text-sm ui-text">v{{ $checklist->version }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Active</p>
                    <p class="mt-1 text-sm ui-text">{{ $checklist->is_active ? 'Yes' : 'No' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Sync Status</p>
                    <p class="mt-1 text-sm ui-text">{{ $checklist->sync_status }}</p>
                </div>
            </div>
            <div class="mt-4 border-t ui-border pt-4">
                <p class="text-xs uppercase ui-text-muted">Offline UUID</p>
                <p class="mt-1 text-sm ui-text break-all">{{ $checklist->offline_uuid }}</p>
            </div>
        </x-ui.card>

        <x-ui.card title="Checklist Items">
            @if ($checklist->items->count() > 0)
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Label</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Required</th>
                            <th class="px-4 py-3">Options</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($checklist->items as $item)
                        <tr>
                            <td class="px-4 py-3 ui-text-muted">{{ $item->sort_order }}</td>
                            <td class="px-4 py-3 ui-text">{{ $item->labelForLocale() }}</td>
                            <td class="px-4 py-3 ui-text-muted">{{ $item->item_type }}</td>
                            <td class="px-4 py-3 ui-text-muted">{{ $item->is_required ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-3 ui-text-muted">
                                {{ is_array($item->options) ? implode(', ', $item->options) : '-' }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @else
                <x-ui.empty-state title="No Items" description="This checklist has no configured items." />
            @endif
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button :href="route('inspections.create', ['inspection_checklist_id' => $checklist->id])" variant="primary" size="md">Start Inspection With This
                Checklist</x-ui.button>
        </div>
    </div>
@endsection
