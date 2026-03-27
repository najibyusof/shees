@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="$training->titleForLocale()" subtitle="Assignment, completion tracking, and certificate lifecycle.">
        <x-slot:actions>
            <x-ui.button :href="route('trainings.index')" variant="secondary" size="md">Back to List</x-ui.button>
            <x-ui.button :href="route('trainings.edit', $training)" variant="primary" size="md">Edit Training</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <x-ui.card title="Training Overview">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs uppercase ui-text-muted">Default Title</p>
                    <p class="mt-1 text-sm ui-text">{{ $training->title }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">English Title</p>
                    <p class="mt-1 text-sm ui-text">{{ $training->title_translations['en'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Indonesian Title</p>
                    <p class="mt-1 text-sm ui-text">{{ $training->title_translations['id'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Validity</p>
                    <p class="mt-1 text-sm ui-text">{{ $training->certificate_validity_days }} days</p>
                </div>
            </div>

            <div class="mt-5 border-t ui-border pt-4">
                <p class="text-xs uppercase ui-text-muted">Description</p>
                <p class="mt-1 whitespace-pre-line text-sm ui-text">{{ $training->descriptionForLocale() ?? '-' }}</p>
            </div>
        </x-ui.card>

        <x-ui.card title="Assign Users" subtitle="Assign one or more users to this training.">
            <form method="POST" action="{{ route('trainings.assign-users', $training) }}" class="space-y-3">
                @csrf
                <select name="user_ids[]" multiple
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                @error('user_ids')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <x-ui.button type="submit" variant="primary" size="md">Assign Selected Users</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Assigned Users & Completion" subtitle="Track progress by user.">
            @if ($training->users->count() > 0)
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Role(s)</th>
                            <th class="px-4 py-3">Assigned</th>
                            <th class="px-4 py-3">Completion</th>
                            <th class="px-4 py-3">Completed At</th>
                            <th class="px-4 py-3 text-right">Update</th>
                        </tr>
                    </x-slot:head>

                    @foreach ($training->users as $assignedUser)
                        <tr>
                            <td class="px-4 py-3 font-medium ui-text">{{ $assignedUser->name }}</td>
                            <td class="px-4 py-3 ui-text-muted">
                                {{ $assignedUser->roles->pluck('name')->join(', ') ?: '-' }}</td>
                            <td class="px-4 py-3 ui-text-muted">
                                {{ optional($assignedUser->pivot->assigned_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 ui-text-muted">{{ $assignedUser->pivot->completion_status }}</td>
                            <td class="px-4 py-3 ui-text-muted">
                                {{ optional($assignedUser->pivot->completed_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST"
                                    action="{{ route('trainings.mark-completion', [$training, $assignedUser]) }}"
                                    class="inline-flex items-center gap-2">
                                    @csrf
                                    <select name="completion_status"
                                        class="rounded-lg border ui-border ui-surface px-2 py-1 text-xs ui-text">
                                        @foreach (['assigned', 'in_progress', 'completed'] as $status)
                                            <option value="{{ $status }}" @selected($assignedUser->pivot->completion_status === $status)>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-ui.button type="submit" variant="secondary" size="sm">Save</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @else
                <x-ui.empty-state title="No Users Assigned" description="Assign users to start completion tracking." />
            @endif
        </x-ui.card>

        <x-ui.card title="Upload Certificate" subtitle="Upload certificate documents and expiry dates.">
            <form method="POST" action="{{ route('trainings.upload-certificate', $training) }}"
                enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="user_id" class="mb-1.5 block text-sm font-medium ui-text-muted">User</label>
                    <select id="user_id" name="user_id" required
                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                        <option value="">Select user</option>
                        @foreach ($training->users as $assignedUser)
                            <option value="{{ $assignedUser->id }}">{{ $assignedUser->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-ui.form-input name="issued_at" type="date" label="Issued Date" />
                <x-ui.form-input name="expires_at" type="date" label="Expiry Date" />
                <div class="sm:col-span-2">
                    <label for="certificate" class="mb-1.5 block text-sm font-medium ui-text-muted">Certificate File</label>
                    <input id="certificate" name="certificate" type="file" required
                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm"
                        accept=".pdf,.jpg,.jpeg,.png,.webp">
                </div>
                <div class="sm:col-span-2">
                    <x-ui.button type="submit" variant="primary" size="md">Upload Certificate</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Certificates" subtitle="Uploaded certificates and expiry status.">
            @if ($training->certificates->count() > 0)
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">File</th>
                            <th class="px-4 py-3">Issued</th>
                            <th class="px-4 py-3">Expires</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </x-slot:head>

                    @foreach ($training->certificates as $certificate)
                        @php
                            $expiresSoon =
                                $certificate->expires_at &&
                                $certificate->expires_at->between(now(), now()->addDays(30));
                            $isExpired = $certificate->expires_at && $certificate->expires_at->isPast();
                        @endphp
                        <tr>
                            <td class="px-4 py-3 ui-text">{{ $certificate->user?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ Storage::disk('public')->url($certificate->file_path) }}" target="_blank"
                                    class="text-sm ui-text hover:underline">
                                    {{ $certificate->original_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 ui-text-muted">
                                {{ optional($certificate->issued_at)->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3 ui-text-muted">
                                {{ optional($certificate->expires_at)->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$isExpired ? 'error' : ($expiresSoon ? 'warning' : 'success')">
                                    {{ $isExpired ? 'Expired' : ($expiresSoon ? 'Expiring Soon' : 'Valid') }}
                                </x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @else
                <x-ui.empty-state title="No Certificates"
                    description="Upload training certificates to enable expiry tracking." />
            @endif
        </x-ui.card>
    </div>
@endsection
