@extends('layouts.app')

@section('header')
    <x-ui.page-header title="User Management"
        subtitle="Manage account access, role assignments, and lifecycle controls across the platform.">
        <x-slot:actions>
            @can('create', App\Models\User::class)
                <x-ui.button :href="route('admin.users.create')" variant="primary" size="md">Create User</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        @php
            $activeFilters =
                collect([$filters['status'] ?? null, $filters['date_from'] ?? null, $filters['date_to'] ?? null])
                    ->filter(fn($value) => filled($value))
                    ->count() + count($filters['role_ids'] ?? []);

            $columns = [
                ['key' => 'name', 'label' => 'Name / Title', 'sortable' => true],
                ['key' => 'email', 'label' => 'Email', 'sortable' => true],
                ['key' => 'roles', 'label' => 'Role'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'created_at', 'label' => 'Date', 'sortable' => true],
                ['key' => 'actions', 'label' => 'Actions', 'th_class' => 'text-right'],
            ];
        @endphp

        <x-ui.card title="Users"
            subtitle="Search, filter, and sort user accounts with a consistent SaaS datatable experience.">
            <x-ui.data-table :columns="$columns" :rows="$users" :paginator="$users" :search="$filters['search'] ?? ''" :sort="$sort"
                :direction="$direction" :filterable="true" :filters-count="$activeFilters" row-view="admin.users.partials.table-row"
                :bulk-action-url="route('admin.users.bulk-action')" :bulk-status-options="['verified', 'unverified']" record-label="users" empty="No users matched your current filters.">
                <x-slot:filters>
                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-medium ui-text">Status</label>
                        <select id="status" name="status"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                            <option value="">All Statuses</option>
                            <option value="verified" @selected(($filters['status'] ?? '') === 'verified')>Verified</option>
                            <option value="unverified" @selected(($filters['status'] ?? '') === 'unverified')>Unverified</option>
                        </select>
                    </div>

                    <div>
                        <label for="role_ids" class="mb-1.5 block text-sm font-medium ui-text">Role</label>
                        <select id="role_ids" name="role_ids[]" multiple
                            class="min-h-24 w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(in_array($role->id, $filters['role_ids'] ?? [], true))>
                                    {{ $role->name }}
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
    </div>
@endsection
