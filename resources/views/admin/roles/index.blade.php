@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Role & Permission Management"
        subtitle="Create, govern, and audit access roles with grouped permission control across every module.">
        <x-slot:actions>
            @can('create', App\Models\Role::class)
                <x-ui.button :href="route('admin.roles.create')" variant="primary" size="md">Create Role</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <x-ui.card title="Role Filters" subtitle="Search roles by name or slug.">
            <form method="GET" action="{{ route('admin.roles') }}" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]">
                <x-ui.form-input name="search" label="Search" placeholder="Search by role name or slug"
                    :value="$search" />

                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="primary" size="md">Search</x-ui.button>
                    <x-ui.button :href="route('admin.roles')" variant="secondary" size="md">Reset</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Roles" subtitle="Manage role composition, user assignment, and permission coverage.">
            @if ($roles->count() > 0)
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th class="px-4 py-3">Role Name</th>
                            <th class="px-4 py-3">Users</th>
                            <th class="px-4 py-3">Permissions</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </x-slot:head>

                    @foreach ($roles as $role)
                        @php
                            $isFullAccess = $totalPermissions > 0 && $role->permissions_count === $totalPermissions;
                        @endphp

                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold ui-text">{{ $role->name }}</span>
                                        @if ($isFullAccess)
                                            <x-ui.badge variant="success">Full access</x-ui.badge>
                                        @endif
                                        @if ($role->isProtectedRole())
                                            <x-ui.badge variant="warning">Protected</x-ui.badge>
                                        @endif
                                    </div>
                                    <span class="text-xs ui-text-muted">{{ $role->slug }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm ui-text">{{ $role->users_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm ui-text">{{ $role->permissions_count }}</span>
                                    <span class="text-xs ui-text-muted">/ {{ $totalPermissions }} total</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm ui-text-muted">{{ $role->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @can('view', $role)
                                        <x-ui.button :href="route('admin.roles.show', $role)" variant="ghost" size="sm">View</x-ui.button>
                                    @endcan

                                    @can('update', $role)
                                        <x-ui.button :href="route('admin.roles.edit', $role)" variant="secondary" size="sm">Edit</x-ui.button>
                                    @endcan

                                    @can('delete', $role)
                                        @if ($role->users_count === 0)
                                            <x-ui.modal :title="'Delete ' . $role->name . '?'"
                                                description="This permanently removes the role and detaches all permission mappings."
                                                maxWidth="max-w-md">
                                                <x-slot:trigger>
                                                    <x-ui.button variant="danger" size="sm">Delete</x-ui.button>
                                                </x-slot:trigger>

                                                <x-slot:actions>
                                                    <x-ui.button variant="secondary" @click="open = false">Cancel</x-ui.button>
                                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-ui.button type="submit" variant="danger" size="md">Delete
                                                            Role</x-ui.button>
                                                    </form>
                                                </x-slot:actions>
                                            </x-ui.modal>
                                        @else
                                            <x-ui.badge variant="warning">Assigned to users</x-ui.badge>
                                        @endif
                        @endif
        </div>
        </td>
        </tr>
        @endforeach
        </x-ui.table>

        <div class="mt-4 flex justify-end">
            <x-ui.pagination :paginator="$roles" />
        </div>
    @else
        <x-ui.empty-state title="No Roles Found"
            description="Create your first role to start managing grouped permissions and access coverage.">
            <x-slot:actions>
                @can('create', App\Models\Role::class)
                    <x-ui.button :href="route('admin.roles.create')" variant="primary" size="md">Create Role</x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.empty-state>
        @endif
        </x-ui.card>
        </div>
    @endsection
