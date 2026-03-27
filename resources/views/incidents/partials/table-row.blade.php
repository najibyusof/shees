<td class="px-4 py-3 align-top">
    <div>
        <p class="font-medium ui-text">{{ $row->title }}</p>
        <p class="mt-1 text-xs ui-text-muted">{{ \Illuminate\Support\Str::limit((string) $row->location, 48) }}</p>
    </div>
</td>

<td class="px-4 py-3 align-top">
    <x-ui.status-badge :status="$row->status" />
</td>

<td class="px-4 py-3 align-top ui-text-muted">
    <div>
        <p>{{ $row->reporter?->name ?? 'Unassigned' }}</p>
        <p class="text-xs">{{ $row->reporter?->email ?? '-' }}</p>
    </div>
</td>

<td class="px-4 py-3 align-top">
    <x-ui.badge :variant="match ($row->classification) {
        'Critical' => 'error',
        'Major' => 'warning',
        'Moderate' => 'info',
        default => 'neutral',
    }">
        {{ $row->classification }}
    </x-ui.badge>
</td>

<td class="px-4 py-3 align-top ui-text-muted">
    <p>{{ optional($row->datetime)->format('Y-m-d H:i') ?? '-' }}</p>
    <p class="text-xs">{{ number_format((int) $row->attachments_count) }} attachments</p>
</td>

<td class="px-4 py-3 align-top text-right">
    <div class="inline-flex items-center gap-1">
        @can('view', $row)
            <x-ui.button :href="route('incidents.show', $row)" variant="ghost" size="sm">View</x-ui.button>
        @endcan
        @can('update', $row)
            <x-ui.button :href="route('incidents.edit', $row)" variant="secondary" size="sm">Edit</x-ui.button>
        @endcan
    </div>
</td>
