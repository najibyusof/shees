@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Incident Management" subtitle="Track, review, and maintain reported incidents.">
        <x-slot:actions>
            @can('create', App\Models\Incident::class)
                <x-ui.button :href="route('incidents.create')" variant="primary" size="md">Create Incident</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        @php
            $activeFilters =
                collect([
                    $filters['status'] ?? null,
                    $filters['classification'] ?? null,
                    $filters['assigned_to'] ?? null,
                    $filters['date_from'] ?? null,
                    $filters['date_to'] ?? null,
                ])
                    ->filter(fn($value) => filled($value))
                    ->count() + count($filters['role_ids'] ?? []);

            $columns = [
                ['key' => 'title', 'label' => 'Name / Title', 'sortable' => true, 'class' => 'font-medium ui-text'],
                ['key' => 'status', 'label' => 'Status', 'sortable' => true],
                ['key' => 'reported_by', 'label' => 'Assigned To'],
                ['key' => 'classification', 'label' => 'Classification', 'sortable' => true],
                ['key' => 'datetime', 'label' => 'Date', 'sortable' => true],
                ['key' => 'actions', 'label' => 'Actions', 'th_class' => 'text-right'],
            ];
        @endphp

        <x-ui.card title="Incident List"
            subtitle="Search, filter, and process incidents with sticky headers, bulk actions, and persistent URL state.">
            <x-ui.data-table :columns="$columns" :rows="$incidents" :paginator="$incidents" :search="$filters['search']" :sort="$sort"
                :direction="$direction" :filterable="true" :filters-count="$activeFilters" :bulk-action-url="route('incidents.bulk-action')" :bulk-status-options="\App\Models\Incident::STATUSES"
                row-view="incidents.partials.table-row" record-label="incidents"
                empty="No incidents matched your current search and filters.">
                <x-slot:filters>
                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-medium ui-text">Status</label>
                        <select id="status" name="status"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                            <option value="">All Statuses</option>
                            @foreach (\App\Models\Incident::STATUSES as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="role_ids" class="mb-1.5 block text-sm font-medium ui-text">Role</label>
                        <select id="role_ids" name="role_ids[]" multiple
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text min-h-24">
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(in_array($role->id, $filters['role_ids'] ?? [], true))>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="assigned_to" class="mb-1.5 block text-sm font-medium ui-text">Assigned User</label>
                        <select id="assigned_to" name="assigned_to"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                            <option value="">All Users</option>
                            @foreach ($assignees as $assignee)
                                <option value="{{ $assignee->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $assignee->id)>
                                    {{ $assignee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="classification" class="mb-1.5 block text-sm font-medium ui-text">Classification</label>
                        <select id="classification" name="classification"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                            <option value="">All Classifications</option>
                            @foreach (\App\Models\Incident::CLASSIFICATIONS as $classification)
                                <option value="{{ $classification }}" @selected(($filters['classification'] ?? '') === $classification)>
                                    {{ $classification }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date_from" class="mb-1.5 block text-sm font-medium ui-text">Date From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                    </div>

                    <div>
                        <label for="date_to" class="mb-1.5 block text-sm font-medium ui-text">Date To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                    </div>
                </x-slot:filters>
            </x-ui.data-table>
        </x-ui.card>
    </div>
@endsection
