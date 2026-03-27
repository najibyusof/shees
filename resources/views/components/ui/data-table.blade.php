@props([
    'columns' => [],
    'rows' => [],
    'paginator' => null,
    'search' => '',
    'filterable' => false,
    'sort' => null,
    'direction' => 'asc',
    'filtersCount' => 0,
    'bulkActionUrl' => null,
    'bulkActions' => [
        'update_status' => 'Update status',
        'delete' => 'Delete',
    ],
    'bulkStatusOptions' => [],
    'rowView' => null,
    'recordLabel' => 'records',
    'empty' => 'No records found.',
])

@php
    $rowCollection =
        $rows instanceof \Illuminate\Contracts\Pagination\Paginator ? $rows->getCollection() : collect($rows);
    $rowIds = $rowCollection->pluck('id')->filter()->values()->all();
    $activeFilters = (int) $filtersCount;
    $recordTotal = $paginator?->total() ?? $rowCollection->count();
    $currentPage = $paginator?->currentPage() ?? 1;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-4']) }} x-data="{
    filterOpen: {{ $filterable ? 'true' : 'false' }},
    selectedIds: [],
    rowIds: @js($rowIds),
    bulkAction: 'update_status',
    bulkStatus: '',
    confirmOpen: false,
    toggleAll(checked) {
        this.selectedIds = checked ? [...this.rowIds] : [];
    },
    isAllSelected() {
        return this.rowIds.length > 0 && this.selectedIds.length === this.rowIds.length;
    },
    canSubmitBulk() {
        if (this.selectedIds.length === 0) {
            return false;
        }

        if (this.bulkAction === 'update_status') {
            return this.bulkStatus !== '';
        }

        return true;
    },
    submitBulk() {
        this.confirmOpen = false;
        this.$refs.bulkForm.submit();
    }
}">
    <form method="GET" action="{{ request()->url() }}" x-ref="searchForm" class="space-y-4">
        @if (filled($sort))
            <input type="hidden" name="sort" value="{{ $sort }}">
        @endif
        @if (filled($direction))
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-md">
                <input type="search" name="search" value="{{ $search }}"
                    @input.debounce.300ms="$refs.searchForm.requestSubmit()"
                    placeholder="Search by name, email, or title..."
                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 pr-10 text-sm text-gray-700 shadow-sm transition focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" />
                <svg class="pointer-events-none absolute right-3 top-2.5 h-5 w-5 text-gray-400" viewBox="0 0 20 20"
                    fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M9 3a6 6 0 104.472 10.001l3.763 3.764a1 1 0 001.414-1.414l-3.764-3.763A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z"
                        clip-rule="evenodd" />
                </svg>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($filterable)
                    <x-ui.button type="button" variant="secondary" size="sm" @click="filterOpen = !filterOpen">
                        Filters
                        @if ($activeFilters > 0)
                            <span
                                class="ml-1 rounded-full bg-teal-100 px-2 py-0.5 text-xs text-teal-700 dark:bg-teal-900/30 dark:text-teal-200">
                                {{ $activeFilters }}
                            </span>
                        @endif
                    </x-ui.button>
                @endif
                {{ $toolbar ?? '' }}
            </div>
        </div>

        @if ($filterable)
            <div x-cloak x-show="filterOpen" x-transition:enter="transition duration-200 ease-out"
                x-transition:enter-start="-translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                class="rounded-2xl border border-gray-200 bg-gradient-to-b from-white to-gray-50 p-4 shadow-sm dark:border-gray-700 dark:from-gray-900 dark:to-gray-800/70">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {{ $filters ?? '' }}
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <x-ui.button type="submit" variant="primary" size="sm">Apply Filters</x-ui.button>
                    <x-ui.button :href="request()->url()" variant="secondary" size="sm">Reset Filters</x-ui.button>
                </div>
            </div>
        @endif
    </form>

    @if ($bulkActionUrl)
        <form method="POST"
            action="{{ $bulkActionUrl }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
            x-ref="bulkForm"
            class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900/80">
            @csrf
            <input type="hidden" name="action" :value="bulkAction">
            <input type="hidden" name="status" :value="bulkStatus">

            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="selected[]" :value="id">
            </template>

            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <p class="text-sm ui-text-muted"><span class="font-semibold ui-text" x-text="selectedIds.length"></span>
                    selected</p>
                <div class="flex flex-1 flex-wrap items-center gap-2">
                    <select x-model="bulkAction"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        @foreach ($bulkActions as $actionValue => $actionLabel)
                            <option value="{{ $actionValue }}">{{ $actionLabel }}</option>
                        @endforeach
                    </select>

                    <select x-show="bulkAction === 'update_status'" x-model="bulkStatus"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        <option value="">Select status</option>
                        @foreach ($bulkStatusOptions as $statusOption)
                            <option value="{{ $statusOption }}">{{ ucfirst(str_replace('_', ' ', $statusOption)) }}
                            </option>
                        @endforeach
                    </select>

                    <x-ui.button type="button" variant="danger" size="sm" x-bind:disabled="!canSubmitBulk()"
                        @click="confirmOpen = true">
                        Apply Action
                    </x-ui.button>
                </div>
            </div>

            <div x-cloak x-show="confirmOpen" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60" @click="confirmOpen = false"></div>
                <div class="relative z-10 w-full max-w-md rounded-2xl border ui-border ui-surface p-5 shadow-xl">
                    <h3 class="text-base font-semibold ui-text">Confirm Bulk Action</h3>
                    <p class="mt-1 text-sm ui-text-muted">This will apply the selected action to <span
                            class="font-semibold" x-text="selectedIds.length"></span> record(s).</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" size="sm"
                            @click="confirmOpen = false">Cancel</x-ui.button>
                        <x-ui.button type="button" variant="danger" size="sm"
                            @click="submitBulk()">Confirm</x-ui.button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm ui-text-muted">
            {{ number_format((int) $recordTotal) }} {{ $recordLabel }} total
            @if ($paginator)
                • page {{ $currentPage }} of {{ max(1, (int) $paginator->lastPage()) }}
            @endif
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 shadow-sm dark:border-gray-700">
        <div class="max-h-[70vh] overflow-auto">
            <table class="min-w-full divide-y divide-gray-200 bg-white text-sm dark:divide-gray-700 dark:bg-gray-900">
                <thead
                    class="sticky top-0 z-10 bg-gray-50/95 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 backdrop-blur dark:bg-gray-800/95 dark:text-gray-300">
                    <tr>
                        @if ($bulkActionUrl)
                            <th class="w-12 px-4 py-3">
                                <input type="checkbox" :checked="isAllSelected()"
                                    @change="toggleAll($event.target.checked)"
                                    class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
                            </th>
                        @endif

                        @foreach ($columns as $column)
                            <th class="px-4 py-3 {{ $column['th_class'] ?? '' }}">
                                @if (($column['sortable'] ?? false) === true)
                                    <x-ui.sort-link :column="$column['key']" :label="$column['label']" :sort="$sort"
                                        :direction="$direction" />
                                @else
                                    {{ $column['label'] }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody
                    class="divide-y divide-gray-100 bg-white text-gray-700 dark:divide-gray-800 dark:bg-gray-900 dark:text-gray-200">
                    @forelse ($rows as $row)
                        <tr
                            class="odd:bg-white even:bg-gray-50/60 hover:bg-teal-50/70 dark:odd:bg-gray-900 dark:even:bg-gray-800/45 dark:hover:bg-teal-900/20">
                            @if ($bulkActionUrl)
                                <td class="px-4 py-3 align-top">
                                    <input type="checkbox" x-model="selectedIds" value="{{ $row->id }}"
                                        class="mt-1 h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
                                </td>
                            @endif

                            @if ($rowView)
                                @include($rowView, ['row' => $row])
                            @else
                                @foreach ($columns as $column)
                                    @php
                                        $rawValue = data_get($row, $column['key']);
                                        $value = $rawValue;

                                        if (($column['format'] ?? null) === 'date' && !empty($rawValue)) {
                                            $value = \Illuminate\Support\Carbon::parse($rawValue)->format(
                                                $column['date_format'] ?? 'Y-m-d',
                                            );
                                        }

                                        if (isset($column['prefix'])) {
                                            $value = $column['prefix'] . $value;
                                        }
                                    @endphp
                                    <td class="px-4 py-3 align-top {{ $column['class'] ?? 'ui-text-muted' }}">
                                        {{ $value }}</td>
                                @endforeach
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + ($bulkActionUrl ? 1 : 0) }}"
                                class="px-6 py-16 text-center">
                                @if (trim((string) ($emptyState ?? '')) !== '')
                                    {{ $emptyState }}
                                @else
                                    <div class="mx-auto max-w-md">
                                        <p class="text-base font-semibold ui-text">No records found</p>
                                        <p class="mt-1 text-sm ui-text-muted">{{ $empty }}</p>
                                        <div class="mt-4">
                                            <x-ui.button :href="request()->url()" variant="secondary" size="sm">Reset
                                                Filters</x-ui.button>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($paginator)
        <x-ui.pagination :paginator="$paginator" />
    @endif
</div>
