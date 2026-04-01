<td class="px-4 py-3 align-top">
    <div>
        <p class="font-medium ui-text">{{ $row->titleForLocale() }}</p>
        <p class="mt-1 text-xs ui-text-muted">
            {{ \Illuminate\Support\Str::limit((string) ($row->description ?? ''), 56) ?: 'No description' }}</p>
    </div>
</td>

<td class="px-4 py-3 align-top">
    <x-ui.badge :variant="$row->is_active ? 'success' : 'neutral'">
        {{ $row->is_active ? 'Active' : 'Inactive' }}
    </x-ui.badge>
</td>

<td class="px-4 py-3 align-top ui-text-muted">
    <p>{{ number_format((int) $row->users_count) }} participants</p>
    <p class="text-xs">{{ number_format((int) $row->certificates_count) }} certificates</p>
</td>

<td class="px-4 py-3 align-top ui-text-muted">
    <p>Start: {{ optional($row->starts_at)->format('Y-m-d') ?? '-' }}</p>
    <p class="text-xs">End: {{ optional($row->ends_at)->format('Y-m-d') ?? '-' }}</p>
</td>

<td class="px-4 py-3 align-top text-right">
    <div class="inline-flex items-center gap-1">
        @can('view', $row)
            <x-ui.button :href="route('trainings.show', $row)" variant="ghost" size="sm">View</x-ui.button>
        @endcan
        @can('update', $row)
            <x-ui.button :href="route('trainings.edit', $row)" variant="secondary" size="sm">Edit</x-ui.button>
        @endcan
    </div>
</td>
