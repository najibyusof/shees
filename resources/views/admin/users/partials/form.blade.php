@props([
    'managedUser' => null,
    'roles' => collect(),
    'submitLabel' => 'Save User',
])

@php
    $selectedRoleIds = collect(old('role_ids', $managedUser?->roles?->pluck('id')->all() ?? []))
        ->map(fn ($roleId) => (int) $roleId)
        ->all();
@endphp

<div class="space-y-6">
    <x-ui.card title="User Details" subtitle="Basic account details used for authentication and communication.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.form-input name="name" label="Name" :value="old('name', $managedUser?->name)" required
                class="sm:col-span-2" autocomplete="name" />

            <x-ui.form-input name="email" label="Email" type="email" :value="old('email', $managedUser?->email)" required
                class="sm:col-span-2" autocomplete="email" />

            <div>
                <x-ui.form-input name="password" label="Password" type="password"
                    :required="! $managedUser" autocomplete="new-password" />
                @if ($managedUser)
                    <p class="mt-1 text-xs ui-text-muted">Leave blank to keep the current password.</p>
                @endif
            </div>

            <x-ui.form-input name="password_confirmation" label="Confirm Password" type="password"
                :required="! $managedUser" autocomplete="new-password" />
        </div>
    </x-ui.card>

    <x-ui.card title="Role Assignment" subtitle="Permissions are inherited from the assigned roles.">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($roles as $role)
                <label class="flex items-start gap-3 rounded-xl border ui-border ui-surface-soft px-4 py-3 transition hover:ui-surface">
                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                        @checked(in_array($role->id, $selectedRoleIds, true))
                        class="mt-0.5 rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                    <span>
                        <span class="block text-sm font-medium ui-text">{{ $role->name }}</span>
                        @if ($role->description)
                            <span class="mt-0.5 block text-xs ui-text-muted">{{ $role->description }}</span>
                        @endif
                    </span>
                </label>
            @endforeach
        </div>

        @error('role_ids')
            <p class="mt-3 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
        @enderror

        @error('role_ids.*')
            <p class="mt-2 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
        @enderror
    </x-ui.card>

    <div class="flex items-center justify-end gap-2">
        <x-ui.button :href="route('admin.users')" variant="secondary" size="md">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary" size="md">{{ $submitLabel }}</x-ui.button>
    </div>
</div>
