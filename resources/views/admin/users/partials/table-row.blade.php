@php
    $linkedRecords =
        (int) $row->reported_incidents_count +
        (int) $row->trainings_count +
        (int) $row->inspections_count +
        (int) $row->site_audits_created_count;
@endphp

<td class="px-4 py-3 align-top">
    <div class="flex items-center gap-3">
        <span
            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-slate-900 to-teal-700 text-sm font-semibold text-white dark:from-gray-700 dark:to-teal-700">
            {{ strtoupper(substr($row->name, 0, 1)) }}
        </span>
        <div>
            <p class="font-medium ui-text">{{ $row->name }}</p>
            <p class="text-xs ui-text-muted">#{{ $row->id }}</p>
        </div>
    </div>
</td>

<td class="px-4 py-3 align-top ui-text-muted">{{ $row->email }}</td>

<td class="px-4 py-3 align-top">
    <div class="flex flex-wrap gap-2">
        @forelse ($row->roles as $role)
            <x-ui.badge :variant="in_array($role->name, ['Admin', 'Manager'], true) ? 'info' : 'neutral'">
                {{ $role->name }}
            </x-ui.badge>
        @empty
            <span class="text-xs ui-text-muted">No roles assigned</span>
        @endforelse
    </div>
</td>

<td class="px-4 py-3 align-top">
    <x-ui.badge :variant="$row->email_verified_at ? 'success' : 'warning'">
        {{ $row->email_verified_at ? 'Verified' : 'Unverified' }}
    </x-ui.badge>
</td>

<td class="px-4 py-3 align-top ui-text-muted">{{ $row->created_at->format('Y-m-d') }}</td>

<td class="px-4 py-3 align-top text-right">
    <div class="inline-flex flex-wrap justify-end gap-2">
        @can('view', $row)
            <x-ui.button :href="route('admin.users.show', $row)" variant="ghost" size="sm">View</x-ui.button>
        @endcan

        @can('update', $row)
            <x-ui.button :href="route('admin.users.edit', $row)" variant="secondary" size="sm">Edit</x-ui.button>
        @endcan

        @can('delete', $row)
            @if ($linkedRecords === 0)
                <x-ui.modal title="Delete {{ $row->name }}?"
                    description="This action permanently removes the account and role assignments. This cannot be undone."
                    maxWidth="max-w-md">
                    <x-slot:trigger>
                        <x-ui.button variant="danger" size="sm">Delete</x-ui.button>
                    </x-slot:trigger>

                    <x-slot:actions>
                        <x-ui.button variant="secondary" @click="open = false">Cancel</x-ui.button>
                        <form method="POST" action="{{ route('admin.users.destroy', $row) }}">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="md">Delete User</x-ui.button>
                        </form>
                    </x-slot:actions>
                </x-ui.modal>
            @else
                <x-ui.badge variant="warning">Has Activity</x-ui.badge>
            @endif
        @elseif (auth()->id() === $row->id)
            <x-ui.badge variant="warning">Current User</x-ui.badge>
        @elseif ($row->isProtectedAccount())
            <x-ui.badge variant="warning">Protected</x-ui.badge>
            @endif
        </div>
    </td>
