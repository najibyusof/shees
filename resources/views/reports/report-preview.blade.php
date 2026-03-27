<x-ui.card title="Report Preview" subtitle="Live preview of the generated adhoc report.">
    <div class="mb-3 flex items-center justify-between">
        <p class="text-sm ui-text-muted">Total results: {{ number_format((int) $previewRows->total()) }}</p>
        <p class="text-sm ui-text-muted">Page {{ $previewRows->currentPage() }} of {{ max(1, $previewRows->lastPage()) }}
        </p>
    </div>

    @if ($previewRows->count() > 0)
        <x-ui.table>
            <x-slot name="head">
                <tr>
                    @foreach ($columnLabels as $label)
                        <th class="px-4 py-3">{{ $label }}</th>
                    @endforeach
                </tr>
            </x-slot>

            @foreach ($previewRows as $row)
                <tr>
                    @foreach ($selectedFields as $field)
                        <td class="px-4 py-3 ui-text-muted">{{ data_get($row, $field) ?? '-' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </x-ui.table>

        <div class="mt-4">
            <x-ui.pagination :paginator="$previewRows" />
        </div>
    @else
        <x-ui.empty-state title="No records found"
            description="No rows match your selected fields and filters. Try adjusting your filter logic." />
    @endif
</x-ui.card>
