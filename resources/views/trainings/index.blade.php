@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Training Management" subtitle="Create programs, assign users, and track certifications.">
        <x-slot:actions>
            @can('create', \App\Models\Training::class)
                <x-ui.button :href="route('trainings.create')" variant="primary" size="md">Create Training</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    @php
        $activeFilters = collect([
            $filters['status'] ?? null,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
        ])
            ->filter(fn($value) => filled($value))
            ->count();

        $columns = [
            ['key' => 'title', 'label' => 'Name / Title', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'assigned', 'label' => 'Assigned To'],
            ['key' => 'starts_at', 'label' => 'Date', 'sortable' => true],
            ['key' => 'actions', 'label' => 'Actions', 'th_class' => 'text-right'],
        ];
    @endphp

    <x-ui.card title="Training Programs"
        subtitle="Unified datatable with advanced filtering and sorting for training operations.">
        <x-ui.data-table :columns="$columns" :rows="$trainings" :paginator="$trainings" :search="$filters['search'] ?? ''" :sort="$sort"
            :direction="$direction" :filterable="true" :filters-count="$activeFilters" row-view="trainings.partials.table-row" :bulk-action-url="route('trainings.bulk-action')"
            :bulk-status-options="['active', 'inactive']" record-label="trainings" empty="No trainings matched your current search or filters.">
            <x-slot:filters>
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-medium ui-text">Status</label>
                    <select id="status" name="status"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div>
                    <label for="date_from" class="mb-1.5 block text-sm font-medium ui-text">Start Date From</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                </div>

                <div>
                    <label for="date_to" class="mb-1.5 block text-sm font-medium ui-text">End Date To</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                </div>

                <div>
                    <label for="per_page" class="mb-1.5 block text-sm font-medium ui-text">Per Page</label>
                    <select id="per_page" name="per_page"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        @foreach ([10, 25, 50] as $pageSize)
                            <option value="{{ $pageSize }}" @selected((int) ($filters['per_page'] ?? 10) === $pageSize)>
                                {{ $pageSize }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </x-slot:filters>
        </x-ui.data-table>
    </x-ui.card>
@endsection
