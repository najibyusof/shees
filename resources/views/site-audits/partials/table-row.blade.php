<td class="px-4 py-3 align-top">
    <div>
        <p class="font-medium ui-text">{{ $row->reference_no }}</p>
        <p class="mt-1 text-xs ui-text-muted">{{ $row->site_name }}</p>
    </div>
</td>

<td class="px-4 py-3 align-top">
    <x-ui.status-badge :status="$row->status" />
</td>

<td class="px-4 py-3 align-top ui-text-muted">
    <p>{{ $row->creator?->name ?? 'Unassigned' }}</p>
    <p class="text-xs">{{ $row->creator?->email ?? '-' }}</p>
</td>

<td class="px-4 py-3 align-top ui-text-muted">
    {{ optional($row->scheduled_for)->format('Y-m-d') ?: 'N/A' }}
</td>

<td class="px-4 py-3 align-top ui-text">
    <p>{{ $row->kpi_score !== null ? number_format($row->kpi_score, 1) . '%' : 'N/A' }}</p>
    <p class="text-xs ui-text-muted">Open NCR: {{ number_format((int) $row->open_ncr_count) }}</p>
</td>

<td class="px-4 py-3 align-top text-right">
    <x-ui.button :href="route('site-audits.show', $row)" variant="ghost" size="sm">Open</x-ui.button>
</td>
