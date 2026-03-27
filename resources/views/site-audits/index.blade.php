@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Site Performance & Audits"
        subtitle="Schedule audits, track KPIs, and manage NCR/corrective actions with approval workflow.">
        <x-slot:actions>
            @can('create', App\Models\SiteAudit::class)
                <x-ui.button :href="route('site-audits.create')">Schedule Audit</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    @php
        $activeFilters =
            collect([
                $filters['status'] ?? null,
                $filters['audit_type'] ?? null,
                $filters['assigned_to'] ?? null,
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null,
            ])
                ->filter(fn($value) => filled($value))
                ->count() + count($filters['role_ids'] ?? []);

        $columns = [
            ['key' => 'reference_no', 'label' => 'Name / Title', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'assigned_to', 'label' => 'Assigned To'],
            ['key' => 'scheduled_for', 'label' => 'Date', 'sortable' => true],
            ['key' => 'kpi_score', 'label' => 'KPI', 'sortable' => true],
            ['key' => 'actions', 'label' => 'Actions', 'th_class' => 'text-right'],
        ];
    @endphp

    <x-ui.card title="Audit Register"
        subtitle="SaaS-style register with persistent search, layered filters, sticky headers, and sortable columns.">
        <x-ui.data-table :columns="$columns" :rows="$siteAudits" :paginator="$siteAudits" :search="$filters['search'] ?? ''" :sort="$sort"
            :direction="$direction" :filterable="true" :filters-count="$activeFilters" row-view="site-audits.partials.table-row" :bulk-action-url="route('site-audits.bulk-action')"
            :bulk-status-options="\App\Models\SiteAudit::STATUSES" record-label="audits" empty="No site audits matched your current search and filters.">
            <x-slot:filters>
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-medium ui-text">Status</label>
                    <select id="status" name="status"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="">All Statuses</option>
                        @foreach (\App\Models\SiteAudit::STATUSES as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
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
                    <label for="audit_type" class="mb-1.5 block text-sm font-medium ui-text">Audit Type</label>
                    <select id="audit_type" name="audit_type"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="">All Types</option>
                        @foreach ($auditTypes as $auditType)
                            <option value="{{ $auditType }}" @selected(($filters['audit_type'] ?? '') === $auditType)>
                                {{ ucfirst(str_replace('_', ' ', $auditType)) }}
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
                            <option value="{{ $pageSize }}" @selected((int) ($filters['per_page'] ?? 25) === $pageSize)>
                                {{ $pageSize }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </x-slot:filters>
        </x-ui.data-table>
    </x-ui.card>
@endsection
