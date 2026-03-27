@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="$role->name"
        subtitle="Review role metadata, grouped permission coverage, and assigned users in one place.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.roles')" variant="secondary" size="md">Back to List</x-ui.button>

            @can('view', $role)
                <x-ui.button :href="route('admin.roles.export', [$role, 'csv'])" variant="secondary" size="md">
                    Export CSV
                </x-ui.button>
                <x-ui.button :href="route('admin.roles.export', [$role, 'pdf'])" variant="secondary" size="md">
                    Export PDF
                </x-ui.button>
            @endcan

            @can('create', App\Models\Role::class)
                <x-ui.modal title="Clone {{ $role->name }}?"
                    description="All permissions from this role will be copied to a new role with '(Clone)' appended to the name."
                    maxWidth="max-w-md">
                    <x-slot:trigger>
                        <x-ui.button variant="secondary" size="md">Clone Role</x-ui.button>
                    </x-slot:trigger>

                    <x-slot:actions>
                        <x-ui.button variant="secondary" @click="open = false">Cancel</x-ui.button>
                        <form method="POST" action="{{ route('admin.roles.clone', $role) }}">
                            @csrf
                            <x-ui.button type="submit" variant="primary" size="md">Clone Role</x-ui.button>
                        </form>
                    </x-slot:actions>
                </x-ui.modal>
            @endcan

            @can('update', $role)
                <x-ui.button :href="route('admin.roles.edit', $role)" variant="primary" size="md">Edit Role</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    @php
        $isFullAccess = $totalPermissions > 0 && $role->permissions_count === $totalPermissions;
    @endphp

    <div class="space-y-6">
        <x-ui.card title="Role Overview" subtitle="Core role details, assignment counts, and system safeguards.">
            <div class="grid gap-4 lg:grid-cols-4">
                <div class="rounded-2xl border ui-border ui-surface-soft p-4 lg:col-span-2">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Role Identity</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold ui-text">{{ $role->name }}</h2>
                        @if ($isFullAccess)
                            <x-ui.badge variant="success">Full access role</x-ui.badge>
                        @endif
                        @if ($role->isProtectedRole())
                            <x-ui.badge variant="warning">Protected</x-ui.badge>
                        @endif
                    </div>
                    <p class="mt-2 text-sm ui-text-muted">Slug: {{ $role->slug }}</p>
                </div>

                <div class="rounded-2xl border ui-border ui-surface-soft p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Permissions</p>
                    <p class="mt-3 text-2xl font-semibold ui-text">{{ $role->permissions_count }}</p>
                    <p class="text-sm ui-text-muted">of {{ $totalPermissions }} available</p>
                </div>

                <div class="rounded-2xl border ui-border ui-surface-soft p-4">
                    <p class="text-xs font-semibold uppercase ui-text-muted">Assigned Users</p>
                    <p class="mt-3 text-2xl font-semibold ui-text">{{ $role->users_count }}</p>
                    <p class="text-sm ui-text-muted">currently attached</p>
                </div>
            </div>

            <div
                class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border ui-border ui-surface-soft px-4 py-3">
                <p class="text-sm ui-text-muted">Created {{ $role->created_at->format('Y-m-d H:i') }}</p>

                <div class="flex items-center gap-2">
                    @can('delete', $role)
                        @if ($role->users_count === 0)
                            <x-ui.modal :title="'Delete ' . $role->name . '?'"
                                description="This permanently removes the role and its permission assignments."
                                maxWidth="max-w-md">
                                <x-slot:trigger>
                                    <x-ui.button variant="danger" size="md">Delete Role</x-ui.button>
                                </x-slot:trigger>

                                <x-slot:actions>
                                    <x-ui.button variant="secondary" @click="open = false">Cancel</x-ui.button>
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="md">Delete Role</x-ui.button>
                                    </form>
                                </x-slot:actions>
                            </x-ui.modal>
                        @else
                            <x-ui.badge variant="warning">Deletion blocked while users are assigned</x-ui.badge>
                        @endif
                        @endif
                    </div>
                </div>
            </x-ui.card>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.9fr)]">
                <x-ui.card title="Grouped Permissions" subtitle="Permissions are organized dynamically by module prefix.">
                    @if ($permissionGroups->count() > 0)
                        <div class="space-y-4">
                            @foreach ($permissionGroups as $group)
                                <section class="rounded-2xl border ui-border ui-surface-soft p-4">
                                    <div class="flex items-center justify-between gap-3 border-b ui-border pb-3">
                                        <div>
                                            <h3 class="text-sm font-semibold ui-text">{{ $group['label'] }}</h3>
                                            <p class="text-xs ui-text-muted">{{ count($group['permissions']) }}
                                                permission{{ count($group['permissions']) === 1 ? '' : 's' }}</p>
                                        </div>
                                        <x-ui.badge :variant="count($group['permissions']) >= 4 ? 'info' : 'neutral'">Module</x-ui.badge>
                                    </div>

                                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($group['permissions'] as $permission)
                                            <div
                                                class="rounded-xl border ui-border bg-white px-4 py-3 shadow-sm dark:bg-gray-800">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <x-ui.badge
                                                        variant="neutral">{{ $permission['action_label'] }}</x-ui.badge>
                                                    <span class="text-sm font-medium ui-text">{{ $permission['name'] }}</span>
                                                </div>
                                                @if ($permission['description'])
                                                    <p class="mt-2 text-xs ui-text-muted">{{ $permission['description'] }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state title="No Permissions Assigned"
                            description="This role does not currently include any permissions." />
                    @endif
                </x-ui.card>

                <x-ui.card title="Assigned Users" subtitle="Users currently inheriting access from this role.">
                    @if ($assignedUsers->count() > 0)
                        <div class="space-y-3">
                            @foreach ($assignedUsers as $assignedUser)
                                <div class="flex items-center gap-3 rounded-xl border ui-border ui-surface-soft px-4 py-3">
                                    <span
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-slate-900 to-teal-700 text-sm font-semibold text-white dark:from-gray-700 dark:to-teal-700">
                                        {{ strtoupper(substr($assignedUser->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium ui-text">{{ $assignedUser->name }}</p>
                                        <p class="truncate text-xs ui-text-muted">{{ $assignedUser->email }}</p>
                                    </div>
                                    @can('view', $assignedUser)
                                        <x-ui.button :href="route('admin.users.show', $assignedUser)" variant="ghost" size="sm">View</x-ui.button>
                                    @endcan
                                </div>
                            @endforeach
                        </div>

                        @if ($role->users_count > $assignedUsers->count())
                            <p class="mt-4 text-xs ui-text-muted">Showing the first {{ $assignedUsers->count() }} assigned
                                users.</p>
                        @endif
                    @else
                        <x-ui.empty-state title="No Users Assigned"
                            description="This role is available but is not currently attached to any users." />
                    @endif
                </x-ui.card>
            </div>
        </div>
    @endsection
