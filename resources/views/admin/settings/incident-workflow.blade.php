@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Incident Workflow Settings"
        subtitle="Configure the unresolved critical comment enforcement rules for each role in the incident approval workflow.">
    </x-ui.page-header>
@endsection

@section('content')
    @if (session('success'))
        <div
            class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.incident-workflow.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        {{-- Global toggle --}}
        <x-ui.card title="Global Enforcement Toggle"
            subtitle="When disabled, no role is blocked from progressing the workflow regardless of unresolved critical comments.">

            <div class="flex items-start gap-4">
                <div class="mt-0.5">
                    <input id="enabled" type="checkbox" name="enabled" value="1"
                        @if (old('enabled', $current['enabled'] ?? true)) checked @endif
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="enabled" class="text-sm font-medium ui-text">
                        Block workflow progression on unresolved critical comments
                    </label>
                    <p class="mt-1 text-xs ui-text-muted">
                        Controlled by <code
                            class="rounded bg-gray-100 px-1 py-0.5 dark:bg-gray-800">INCIDENT_BLOCK_ON_UNRESOLVED_CRITICAL</code>
                        env variable when not overridden here.
                    </p>
                </div>
            </div>
        </x-ui.card>

        {{-- Critical comment types --}}
        <x-ui.card title="Critical Comment Types"
            subtitle="Comments with these types are automatically treated as critical, in addition to comments explicitly flagged as critical by the author.">

            @php
                $savedTypes = old('critical_comment_types', $current['critical_comment_types'] ?? []);
            @endphp

            <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                @foreach ($commentTypeOptions as $value => $label)
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 ui-border hover:bg-gray-50 dark:hover:bg-white/5">
                        <input type="checkbox" name="critical_comment_types[]" value="{{ $value }}"
                            @if (in_array($value, $savedTypes, true)) checked @endif
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="text-sm font-medium ui-text">{{ $label }}</span>
                            <p class="text-xs ui-text-muted">{{ $value }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Per-role enforcement --}}
        <x-ui.card title="Role-Based Enforcement Rules"
            subtitle="Select which roles are blocked from advancing the workflow when unresolved critical comments exist. Rules only apply when the global toggle is enabled.">

            @php
                $savedRoleRules = old('role_rules', $current['role_rules'] ?? []);
            @endphp

            @if ($roles->isEmpty())
                <p class="text-sm ui-text-muted">No roles found. Create roles first.</p>
            @else
                <div class="overflow-hidden rounded-lg border ui-border">
                    <table class="w-full text-sm">
                        <thead class="ui-surface-raised">
                            <tr class="border-b ui-border">
                                <th class="px-4 py-3 text-left font-medium ui-text">Role</th>
                                <th class="px-4 py-3 text-center font-medium ui-text">Enforce Blocking</th>
                                <th class="px-4 py-3 text-left font-medium ui-text-muted">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y ui-divide">
                            @foreach ($roles as $role)
                                @php
                                    $isEnforced = isset($savedRoleRules[$role->name])
                                        ? (bool) data_get($savedRoleRules, $role->name . '.enforce', false)
                                        : (bool) data_get(
                                            $current['role_rules'] ?? [],
                                            $role->name . '.enforce',
                                            false,
                                        );
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-3">
                                        <div class="font-medium ui-text">{{ $role->name }}</div>
                                        <div class="text-xs ui-text-muted">{{ $role->slug }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="role_rules[{{ $role->name }}]" value="1"
                                            @if ($isEnforced) checked @endif
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-4 py-3 text-xs ui-text-muted">
                                        @if ($isEnforced)
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400">
                                                Blocked when critical comments open
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                Can progress freely
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-3 text-xs ui-text-muted">
                    Roles not listed in the original config default to <strong>not enforced</strong>.
                    Checking a role here will enforce the rule for that role.
                </p>
            @endif
        </x-ui.card>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.settings.incident-workflow') }}" class="text-sm ui-text-muted hover:ui-text">
                Reset
            </a>
            <x-ui.button type="submit" variant="primary" size="md">
                Save Settings
            </x-ui.button>
        </div>
    </form>
@endsection
