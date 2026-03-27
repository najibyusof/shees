@extends('layouts.app')

@section('header')
    <x-ui.page-header title="User Administration"
        subtitle="Access controlled with role middleware and UserPolicy permission checks.">
        <x-slot:actions>
            @can('create', App\Models\User::class)
                <x-ui.button variant="primary" size="md">
                    Create User
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6" x-data="usersPreferences({
        prefsKey: 'ui.users.preferences.v1',
        saveUrl: '{{ route('admin.users.preferences') }}',
        csrfToken: '{{ csrf_token() }}',
        serverPrefs: @js($serverPrefs),
        defaults: {
            density: 'comfortable',
            defaultSort: 'created_at',
            defaultDirection: 'desc',
            visibleColumns: { id: true, name: true, email: true, created_at: true },
        }
    })">
        @php
            $columns = [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'sortable' => true,
                    'prefix' => '#',
                    'class' => 'whitespace-nowrap ui-text-muted',
                ],
                ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'class' => 'font-medium ui-text'],
                ['key' => 'email', 'label' => 'Email', 'sortable' => true, 'class' => 'ui-text-muted'],
                [
                    'key' => 'created_at',
                    'label' => 'Joined',
                    'sortable' => true,
                    'format' => 'date',
                    'date_format' => 'Y-m-d',
                    'class' => 'whitespace-nowrap ui-text-muted',
                ],
            ];
        @endphp

        <x-ui.alert type="success" title="Authorized">
            Only users with the users.view permission can see this page.
        </x-ui.alert>

        <x-ui.card title="UI Preferences" subtitle="Persisted in browser for density, visible columns, and default sorting.">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Row Density</label>
                    <select x-model="density"
                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                        <option value="comfortable">Comfortable</option>
                        <option value="compact">Compact</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Default Sort</label>
                    <div class="grid grid-cols-2 gap-2">
                        <select x-model="defaultSort"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="id">ID</option>
                            <option value="name">Name</option>
                            <option value="email">Email</option>
                            <option value="created_at">Joined</option>
                        </select>

                        <select x-model="defaultDirection"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="asc">ASC</option>
                            <option value="desc">DESC</option>
                        </select>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <p class="mb-2 text-sm font-medium ui-text-muted">Visible Columns</p>
                    <div class="flex flex-wrap gap-2">
                        <label
                            class="inline-flex items-center gap-2 rounded-lg border ui-border px-3 py-2 text-sm ui-text-muted">
                            <input type="checkbox" x-model="visibleColumns.id" class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                            <span>ID</span>
                        </label>
                        <label
                            class="inline-flex items-center gap-2 rounded-lg border ui-border px-3 py-2 text-sm ui-text-muted">
                            <input type="checkbox" x-model="visibleColumns.name" class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                            <span>Name</span>
                        </label>
                        <label
                            class="inline-flex items-center gap-2 rounded-lg border ui-border px-3 py-2 text-sm ui-text-muted">
                            <input type="checkbox" x-model="visibleColumns.email" class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                            <span>Email</span>
                        </label>
                        <label
                            class="inline-flex items-center gap-2 rounded-lg border ui-border px-3 py-2 text-sm ui-text-muted">
                            <input type="checkbox" x-model="visibleColumns.created_at" class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                            <span>Joined</span>
                        </label>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex gap-2">
                    <x-ui.button variant="primary" size="md" @click="savePrefsToServer()" x-bind:disabled="saving">
                        <span x-show="!saving">Save Preferences</span>
                        <span x-show="saving" x-cloak>Saving...</span>
                    </x-ui.button>
                    <x-ui.button variant="secondary" size="md" @click="resetPrefs(); savePrefsToServer();">Reset
                        Defaults</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.card>

        <x-ui.card title="Filters" subtitle="Example reusable form input component.">
            <form method="GET" action="{{ route('admin.users') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.form-input name="search" label="Search" placeholder="Name or email" :value="request('search')"
                    class="sm:col-span-2" />

                <x-ui.form-input name="from" type="date" label="From Date" :value="request('from')" />
                <x-ui.form-input name="to" type="date" label="To Date" :value="request('to')" />

                <div class="sm:col-span-2 lg:col-span-4">
                    <x-ui.button type="submit" variant="primary" size="md">
                        Apply Filters
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Users" subtitle="Example reusable table component.">
            <x-slot:footer>
                <x-ui.pagination :paginator="$users" />
            </x-slot:footer>

            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm ui-text-muted">Showing {{ $users->count() }} users on this page.</p>

                <div class="flex items-center gap-2">
                    <x-ui.badge variant="info">
                        Sorted by {{ strtoupper($sort === 'created_at' ? 'joined' : $sort) }} ({{ strtoupper($direction) }})
                    </x-ui.badge>

                    <x-ui.modal title="Invite Team Member"
                        description="This modal demonstrates reusable dialog + form components." triggerLabel="Invite User"
                        triggerVariant="secondary">
                        <form action="#" method="POST" class="space-y-3" @submit.prevent>
                            <x-ui.form-input name="invite_name" label="Full Name" placeholder="Alex Johnson" />
                            <x-ui.form-input name="invite_email" type="email" label="Email"
                                placeholder="alex@example.com" />
                            <x-ui.form-input name="invite_role" label="Role"
                                placeholder="Worker / Supervisor / Auditor" />
                        </form>

                        <x-slot:actions>
                            <x-ui.button variant="secondary" @click="open = false">Cancel</x-ui.button>
                            <x-ui.button variant="primary">Send Invite</x-ui.button>
                        </x-slot:actions>
                    </x-ui.modal>
                </div>
            </div>

            @if ($users->count() > 0)
                <x-ui.data-table :columns="$columns" :rows="$users" :sort="$sort" :direction="$direction"
                    empty="No users found for the selected filters." />
            @else
                <x-ui.empty-state title="No Users Found"
                    description="Try changing your filters or sorting options to find matching records." />
            @endif
        </x-ui.card>
    </div>
@endsection
