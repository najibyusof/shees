@props([
    'moduleKey',
    'moduleLabel',
    'permissions',
])

@php
    $permissionIds = collect($permissions)->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
@endphp

<section class="rounded-2xl border ui-border ui-surface-soft p-4 transition hover:border-teal-300 dark:hover:border-teal-700/70">
    <div class="flex flex-col gap-3 border-b ui-border pb-4 sm:flex-row sm:items-center sm:justify-between">
        <button type="button" class="flex items-center gap-3 text-left" @click="toggleModuleOpen('{{ $moduleKey }}')">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-slate-900 to-teal-700 text-xs font-semibold uppercase tracking-[0.16em] text-white dark:from-gray-700 dark:to-teal-700">
                {{ strtoupper(substr($moduleLabel, 0, 2)) }}
            </span>

            <span>
                <span class="block text-sm font-semibold ui-text">{{ $moduleLabel }}</span>
                <span class="block text-xs ui-text-muted">{{ count($permissionIds) }} permission{{ count($permissionIds) === 1 ? '' : 's' }}</span>
            </span>

            <svg class="h-4 w-4 ui-text-muted transition" :class="isModuleOpen('{{ $moduleKey }}') ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.1 1.02l-4.25 4.5a.75.75 0 0 1-1.1 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>

        <label class="inline-flex items-center gap-3 rounded-xl border ui-border bg-white px-3 py-2 text-sm font-medium ui-text shadow-sm dark:bg-gray-800">
            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                @change="toggleModulePermissions('{{ $moduleKey }}', @js($permissionIds), $event.target.checked)"
                :checked="isModuleFullySelected('{{ $moduleKey }}')"
                x-effect="$el.indeterminate = isModulePartiallySelected('{{ $moduleKey }}')">
            <span>Select all in {{ $moduleLabel }}</span>
        </label>
    </div>

    <div x-show="isModuleOpen('{{ $moduleKey }}')" x-transition.opacity.duration.200ms class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($permissions as $permission)
            <label class="group flex cursor-pointer items-start gap-3 rounded-xl border ui-border bg-white px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md dark:bg-gray-800 dark:hover:border-teal-700/70">
                <input type="checkbox" name="permission_ids[]" value="{{ $permission['id'] }}" x-model="selectedPermissionIds"
                    class="mt-1 h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">

                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold ui-text">{{ $permission['action_label'] }}</span>
                        <x-ui.badge variant="neutral">{{ $permission['name'] }}</x-ui.badge>
                    </span>

                    @if ($permission['description'])
                        <span class="mt-1 block text-xs ui-text-muted">{{ $permission['description'] }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
</section>
