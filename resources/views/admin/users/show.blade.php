@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="$managedUser->name"
        subtitle="Review account details, assigned roles, and recent linked activity.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.users')" variant="secondary" size="md">Back to List</x-ui.button>

            @can('update', $managedUser)
                <x-ui.button :href="route('admin.users.edit', $managedUser)" variant="primary" size="md">Edit User</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    @php
        $linkedRecords = $managedUser->reportedIncidents->count() + $recentTrainings->count();
    @endphp

    <div class="space-y-6">
        <x-ui.card title="User Profile" subtitle="Core account information and role assignments.">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,220px)_minmax(0,1fr)]">
                <div class="rounded-2xl border ui-border ui-surface-soft p-5 text-center">
                    <span class="mx-auto inline-flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-slate-900 to-teal-700 text-2xl font-semibold text-white dark:from-gray-700 dark:to-teal-700">
                        {{ strtoupper(substr($managedUser->name, 0, 1)) }}
                    </span>
                    <h2 class="mt-4 text-lg font-semibold ui-text">{{ $managedUser->name }}</h2>
                    <p class="mt-1 text-sm ui-text-muted">{{ $managedUser->email }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border ui-border ui-surface-soft p-4">
                        <p class="text-xs font-semibold uppercase ui-text-muted">Created</p>
                        <p class="mt-2 text-sm ui-text">{{ $managedUser->created_at->format('Y-m-d H:i') }}</p>
                    </div>

                    <div class="rounded-xl border ui-border ui-surface-soft p-4">
                        <p class="text-xs font-semibold uppercase ui-text-muted">Email Verification</p>
                        <p class="mt-2 text-sm ui-text">
                            {{ $managedUser->email_verified_at ? $managedUser->email_verified_at->format('Y-m-d H:i') : 'Not verified' }}
                        </p>
                    </div>

                    <div class="rounded-xl border ui-border ui-surface-soft p-4 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase ui-text-muted">Roles</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($managedUser->roles as $role)
                                <x-ui.badge :variant="in_array($role->name, ['Admin', 'Manager'], true) ? 'info' : 'neutral'">
                                    {{ $role->name }}
                                </x-ui.badge>
                            @empty
                                <span class="text-sm ui-text-muted">No roles assigned.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    @can('delete', $managedUser)
                        @if ($linkedRecords === 0)
                            <x-ui.modal :title="'Delete ' . $managedUser->name . '?'"
                                description="This permanently removes the account and role assignments. This action cannot be undone."
                                maxWidth="max-w-md">
                                <x-slot:trigger>
                                    <x-ui.button variant="danger" size="md">Delete User</x-ui.button>
                                </x-slot:trigger>

                                <x-slot:actions>
                                    <x-ui.button variant="secondary" @click="open = false">Cancel</x-ui.button>
                                    <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="md">Delete User</x-ui.button>
                                    </form>
                                </x-slot:actions>
                            </x-ui.modal>
                        @else
                            <x-ui.badge variant="warning">Deletion blocked while linked activity exists</x-ui.badge>
                        @endif
                    @elseif ($managedUser->isProtectedAccount())
                        <x-ui.badge variant="warning">Protected account</x-ui.badge>
                    @endif
                </div>
            </x-slot:footer>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.card title="Recent Incidents" subtitle="Latest incident reports submitted by this user.">
                @if ($managedUser->reportedIncidents->count() > 0)
                    <div class="space-y-3">
                        @foreach ($managedUser->reportedIncidents as $incident)
                            <div class="rounded-xl border ui-border ui-surface-soft px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium ui-text">{{ $incident->title }}</p>
                                    <x-ui.status-badge :status="$incident->status" />
                                </div>
                                <p class="mt-1 text-xs ui-text-muted">{{ $incident->location }} | {{ $incident->datetime?->format('Y-m-d H:i') ?? '-' }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="No Incidents" description="This user has not reported any incidents yet." />
                @endif
            </x-ui.card>

            <x-ui.card title="Training Assignments" subtitle="Most recent assigned training records for this user.">
                @if ($recentTrainings->count() > 0)
                    <div class="space-y-3">
                        @foreach ($recentTrainings as $training)
                            <div class="rounded-xl border ui-border ui-surface-soft px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium ui-text">{{ $training->titleForLocale() }}</p>
                                    <x-ui.badge :variant="$training->pivot->completion_status === 'completed' ? 'success' : 'warning'">
                                        {{ str_replace('_', ' ', ucfirst($training->pivot->completion_status)) }}
                                    </x-ui.badge>
                                </div>
                                <p class="mt-1 text-xs ui-text-muted">
                                    Assigned {{ optional($training->pivot->assigned_at)->format('Y-m-d H:i') ?? '-' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="No Training Assignments"
                        description="This user has not been assigned to any training yet." />
                @endif
            </x-ui.card>
        </div>
    </div>
@endsection
