@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Audit Logs"
        subtitle="Track critical user activity across modules with export-ready filtering." />
@endsection

@section('content')
    <div class="space-y-6">
        <x-ui.card title="Filters" subtitle="Narrow by action, module, actor, and time window.">
            <form method="GET" action="{{ route('audit.logs') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div>
                    <label for="action" class="mb-1.5 block text-sm font-medium ui-text">Action</label>
                    <select id="action" name="action" class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="">All Actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>
                                {{ ucfirst($action) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="module" class="mb-1.5 block text-sm font-medium ui-text">Module</label>
                    <select id="module" name="module" class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        <option value="">All Modules</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>
                                {{ ucfirst($module) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="user_id" class="mb-1.5 block text-sm font-medium ui-text">User ID</label>
                    <input id="user_id" name="user_id" type="number" min="1" value="{{ $filters['user_id'] ?? '' }}"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                </div>

                <div>
                    <label for="from" class="mb-1.5 block text-sm font-medium ui-text">From</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                </div>

                <div>
                    <label for="to" class="mb-1.5 block text-sm font-medium ui-text">To</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}"
                        class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                </div>

                <div>
                    <label for="per_page" class="mb-1.5 block text-sm font-medium ui-text">Per Page</label>
                    <select id="per_page" name="per_page" class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                        @foreach ([10, 25, 50, 100] as $pageSize)
                            <option value="{{ $pageSize }}" @selected((int) ($filters['per_page'] ?? 25) === $pageSize)>
                                {{ $pageSize }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 xl:col-span-6 flex flex-wrap items-center justify-between gap-2">
                    <div class="inline-flex gap-2">
                        <x-ui.button type="submit" variant="primary" size="sm">Apply Filters</x-ui.button>
                        <x-ui.button :href="route('audit.logs')" variant="secondary" size="sm">Reset</x-ui.button>
                    </div>
                    <div class="inline-flex gap-2">
                        <x-ui.button :href="route('audit.logs.export', array_merge($filters, ['format' => 'csv']))" variant="secondary" size="sm">Export CSV</x-ui.button>
                        <x-ui.button :href="route('audit.logs.export', array_merge($filters, ['format' => 'pdf']))" variant="secondary" size="sm">Export PDF</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Activity Feed" subtitle="Recent activity for create, update, delete, and approve actions.">
            <x-ui.table empty="No audit logs found for selected filters.">
                <x-slot name="head">
                    <tr>
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Module</th>
                        <th class="px-4 py-3">Record</th>
                        <th class="px-4 py-3">Request ID</th>
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </x-slot>

                @forelse ($logs as $log)
                    @php
                        $metadata = is_array($log->metadata) ? $log->metadata : [];
                        $shortRequestId = isset($metadata['request_id']) ? \Illuminate\Support\Str::limit((string) $metadata['request_id'], 12, '') : '-';
                        $actionVariant = match (strtolower((string) $log->action)) {
                            'create', 'approve', 'approved' => 'success',
                            'update', 'edit', 'submit', 'submitted' => 'info',
                            'delete', 'remove', 'reject', 'rejected' => 'error',
                            default => 'neutral',
                        };
                    @endphp
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$actionVariant">{{ strtoupper((string) $log->action) }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">{{ $log->module }}</td>
                        <td class="px-4 py-3">
                            {{ class_basename($log->auditable_type ?? 'N/A') }}#{{ $log->auditable_id ?? '-' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs ui-text">{{ $shortRequestId }}</td>
                        <td class="px-4 py-3">{{ $metadata['ip_address'] ?? '-' }}</td>
                    </tr>
                @empty
                @endforelse
            </x-ui.table>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </x-ui.card>
    </div>
@endsection
