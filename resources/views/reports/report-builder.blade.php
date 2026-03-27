@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Adhoc Report Builder"
        subtitle="Build dynamic reports with module-aware fields, filters, sorting, preview, and export.">
        <x-slot:actions>
            <x-ui.button :href="route('reports.index')" variant="secondary" size="md">Back to Reports</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6" x-data="reportBuilder({
        module: @js($module),
        fieldsByModule: @js($fieldsByModule),
        selectedFields: @js($selectedFields),
        filters: @js($filters),
        sortField: @js($sortField),
        sortDirection: @js($sortDirection),
        perPage: @js($perPage),
    })">
        <x-ui.card title="Build Report"
            subtitle="Choose module, fields, filter rules, and sorting before generating a preview.">
            <form method="GET" action="{{ route('reports.builder') }}" class="space-y-6">
                <input type="hidden" name="preview" value="1" />

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="module" class="mb-1.5 block text-sm font-medium ui-text">Module</label>
                        <select id="module" name="module" x-model="module"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                            @foreach ($allowedModules as $moduleKey => $moduleLabel)
                                <option value="{{ $moduleKey }}">{{ $moduleLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="sort_field" class="mb-1.5 block text-sm font-medium ui-text">Sort Field</label>
                        <select id="sort_field" name="sort_field" x-model="sortField"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                            <template x-for="field in availableFieldEntries()" :key="'sort-' + field.key">
                                <option :value="field.key">
                                    <span x-text="field.label"></span>
                                </option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label for="sort_direction" class="mb-1.5 block text-sm font-medium ui-text">Sort Direction</label>
                        <select id="sort_direction" name="sort_direction" x-model="sortDirection"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                            <option value="asc">ASC</option>
                            <option value="desc">DESC</option>
                        </select>
                    </div>

                    <div>
                        <label for="per_page" class="mb-1.5 block text-sm font-medium ui-text">Per Page</label>
                        <select id="per_page" name="per_page" x-model="perPage"
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <div class="rounded-xl border ui-border p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-sm font-semibold ui-text">Field Selection</p>
                            <p class="text-xs ui-text-muted">Select report columns</p>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <template x-for="field in availableFieldEntries()" :key="'field-' + field.key">
                                <label class="inline-flex items-center gap-2 rounded-lg border ui-border px-3 py-2 text-sm">
                                    <input type="checkbox" :checked="isFieldSelected(field.key)"
                                        @change="toggleField(field.key, $event.target.checked)"
                                        class="rounded border ui-border">
                                    <span x-text="field.label" class="ui-text"></span>
                                </label>
                            </template>
                        </div>

                        <div class="mt-4 rounded-lg border ui-border p-3" x-show="selectedFields.length > 0">
                            <p class="mb-2 text-xs font-semibold uppercase ui-text-muted">Selected Field Order (Drag to
                                Reorder)</p>
                            <div class="space-y-2">
                                <template x-for="(fieldKey, index) in selectedFields" :key="'selected-' + fieldKey">
                                    <div class="flex items-center justify-between rounded-lg border ui-border bg-white px-3 py-2 text-sm dark:bg-gray-900"
                                        draggable="true" @dragstart="startDrag(index)" @dragover.prevent
                                        @drop="dropOn(index)">
                                        <div class="flex items-center gap-2">
                                            <span class="cursor-grab text-xs ui-text-muted">::</span>
                                            <span class="ui-text" x-text="fieldLabel(fieldKey)"></span>
                                        </div>
                                        <button type="button" class="text-xs text-rose-600"
                                            @click="removeSelectedField(fieldKey)">Remove</button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <template x-for="field in selectedFields" :key="'input-field-' + field">
                            <input type="hidden" name="fields[]" :value="field">
                        </template>
                    </div>

                    <div class="rounded-xl border ui-border p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-sm font-semibold ui-text">Filter Builder</p>
                            <x-ui.button type="button" variant="secondary" size="sm" @click="addFilter()">Add
                                Filter</x-ui.button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(filter, index) in filters" :key="'filter-' + index">
                                <div class="rounded-lg border ui-border p-3">
                                    <div class="grid gap-2 md:grid-cols-5">
                                        <div>
                                            <label class="mb-1 block text-xs ui-text-muted">Field</label>
                                            <select :name="`filters[${index}][field]`" x-model="filter.field"
                                                class="w-full rounded-lg border ui-border px-2 py-2 text-sm ui-surface ui-text">
                                                <option value="">Select field</option>
                                                <template x-for="field in availableFieldEntries()"
                                                    :key="'flt-field-' + field.key">
                                                    <option :value="field.key"><span x-text="field.label"></span>
                                                    </option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs ui-text-muted">Operator</label>
                                            <select :name="`filters[${index}][operator]`" x-model="filter.operator"
                                                class="w-full rounded-lg border ui-border px-2 py-2 text-sm ui-surface ui-text">
                                                <template x-for="operator in operatorsForField(filter.field)"
                                                    :key="`op-${index}-${operator}`">
                                                    <option :value="operator" x-text="operator"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs ui-text-muted">Value</label>
                                            <template x-if="hasOptions(filter.field)">
                                                <select :name="`filters[${index}][value]`" x-model="filter.value"
                                                    class="w-full rounded-lg border ui-border px-2 py-2 text-sm ui-surface ui-text">
                                                    <option value="">Select value</option>
                                                    <template x-for="option in fieldOptions(filter.field)"
                                                        :key="`opt-${index}-${option}`">
                                                        <option :value="option" x-text="formatOptionLabel(option)">
                                                        </option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="!hasOptions(filter.field)">
                                                <input :name="`filters[${index}][value]`" x-model="filter.value"
                                                    :type="inputTypeForField(filter.field)"
                                                    class="w-full rounded-lg border ui-border px-2 py-2 text-sm ui-surface ui-text" />
                                            </template>
                                        </div>

                                        <div x-show="filter.operator === 'between'">
                                            <label class="mb-1 block text-xs ui-text-muted">To</label>
                                            <input :name="`filters[${index}][value_to]`" x-model="filter.value_to"
                                                :type="inputTypeForField(filter.field)"
                                                class="w-full rounded-lg border ui-border px-2 py-2 text-sm ui-surface ui-text" />
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs ui-text-muted">Logic</label>
                                            <div class="flex items-center gap-2">
                                                <select :name="`filters[${index}][boolean]`" x-model="filter.boolean"
                                                    class="w-full rounded-lg border ui-border px-2 py-2 text-sm ui-surface ui-text">
                                                    <option value="and">AND</option>
                                                    <option value="or">OR</option>
                                                </select>
                                                <x-ui.button type="button" variant="danger" size="sm"
                                                    @click="removeFilter(index)">Remove</x-ui.button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <p x-show="filters.length === 0" class="text-sm ui-text-muted">No filters added.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 border-t ui-border pt-4">
                    <div class="inline-flex gap-2">
                        <x-ui.button type="submit" variant="primary" size="md">Generate Report</x-ui.button>
                        <x-ui.button :href="route('reports.builder', ['module' => $module])" variant="secondary" size="md">Reset</x-ui.button>
                    </div>

                    <div class="inline-flex gap-2">
                        <button type="submit" formaction="{{ route('reports.builder.export', ['format' => 'csv']) }}"
                            formmethod="GET"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            Export CSV
                        </button>
                        <button type="submit" formaction="{{ route('reports.builder.export', ['format' => 'pdf']) }}"
                            formmethod="GET"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            Export PDF
                        </button>
                    </div>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Save Report Configuration" subtitle="Optionally save this setup as a reusable report preset.">
            <form method="POST" action="{{ route('reports.builder.presets.store') }}"
                class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]">
                @csrf
                <input type="hidden" name="module" :value="module">
                <input type="hidden" name="sort_field" :value="sortField">
                <input type="hidden" name="sort_direction" :value="sortDirection">
                <input type="hidden" name="per_page" :value="perPage">

                <template x-for="field in selectedFields" :key="'save-field-' + field">
                    <input type="hidden" name="fields[]" :value="field">
                </template>

                <template x-for="(filter, index) in filters" :key="'save-filter-' + index">
                    <div class="hidden">
                        <input type="hidden" :name="`filters[${index}][field]`" :value="filter.field">
                        <input type="hidden" :name="`filters[${index}][operator]`" :value="filter.operator">
                        <input type="hidden" :name="`filters[${index}][value]`" :value="filter.value">
                        <input type="hidden" :name="`filters[${index}][value_to]`" :value="filter.value_to">
                        <input type="hidden" :name="`filters[${index}][boolean]`" :value="filter.boolean">
                    </div>
                </template>

                <x-ui.form-input name="name" label="Preset Name" placeholder="Weekly Incident Dashboard" />

                <div class="flex items-end">
                    <x-ui.button type="submit" variant="secondary" size="md">Save Preset</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($previewEnabled)
            @include('reports.report-preview', [
                'previewRows' => $previewRows,
                'columnLabels' => $columnLabels,
                'selectedFields' => $selectedFields,
            ])
        @endif
    </div>

    <script>
        function reportBuilder(config) {
            return {
                module: config.module,
                fieldsByModule: config.fieldsByModule,
                selectedFields: config.selectedFields || [],
                filters: config.filters && config.filters.length ? config.filters : [],
                sortField: config.sortField || '',
                sortDirection: config.sortDirection || 'desc',
                perPage: config.perPage || 25,
                dragIndex: null,
                init() {
                    this.normalizeModuleState();
                    this.$watch('module', () => {
                        this.normalizeModuleState();
                    });
                },
                normalizeModuleState() {
                    const availableEntries = this.availableFieldEntries();
                    const available = new Set(availableEntries.map((entry) => entry.key));

                    this.selectedFields = this.selectedFields.filter((field) => available.has(field));

                    if (this.selectedFields.length === 0) {
                        this.selectedFields = availableEntries.slice(0, 5).map((entry) => entry.key);
                    }

                    this.filters = this.filters.filter((filter) => !filter.field || available.has(filter.field));

                    if (!available.has(this.sortField)) {
                        this.sortField = this.selectedFields[0] || availableEntries[0]?.key || '';
                    }
                },
                availableFieldEntries() {
                    const fieldMap = this.fieldsByModule[this.module] || {};
                    return Object.entries(fieldMap).map(([key, meta]) => ({
                        key,
                        label: meta.label || key,
                        type: meta.type || 'string',
                        options: meta.options || [],
                    }));
                },
                fieldMeta(fieldKey) {
                    return (this.fieldsByModule[this.module] || {})[fieldKey] || null;
                },
                fieldLabel(fieldKey) {
                    const meta = this.fieldMeta(fieldKey);
                    return (meta && meta.label) ? meta.label : fieldKey;
                },
                isFieldSelected(fieldKey) {
                    return this.selectedFields.includes(fieldKey);
                },
                toggleField(fieldKey, checked) {
                    if (checked) {
                        if (!this.selectedFields.includes(fieldKey)) {
                            this.selectedFields.push(fieldKey);
                        }
                        if (!this.sortField) {
                            this.sortField = fieldKey;
                        }
                        return;
                    }

                    this.removeSelectedField(fieldKey);
                },
                removeSelectedField(fieldKey) {
                    this.selectedFields = this.selectedFields.filter((field) => field !== fieldKey);
                    if (this.sortField === fieldKey) {
                        this.sortField = this.selectedFields[0] || '';
                    }
                },
                startDrag(index) {
                    this.dragIndex = index;
                },
                dropOn(index) {
                    if (this.dragIndex === null || this.dragIndex === index) {
                        this.dragIndex = null;
                        return;
                    }

                    const moved = this.selectedFields.splice(this.dragIndex, 1)[0];
                    this.selectedFields.splice(index, 0, moved);
                    this.dragIndex = null;
                },
                operatorsForField(fieldKey) {
                    const type = this.fieldMeta(fieldKey)?.type || 'string';
                    if (type === 'boolean' || type === 'enum') {
                        return ['=', '!='];
                    }
                    if (type === 'number' || type === 'date' || type === 'datetime') {
                        return ['=', '!=', '>', '<', 'between'];
                    }
                    return ['=', '!=', 'like'];
                },
                hasOptions(fieldKey) {
                    const options = this.fieldMeta(fieldKey)?.options || [];
                    return Array.isArray(options) && options.length > 0;
                },
                fieldOptions(fieldKey) {
                    return this.fieldMeta(fieldKey)?.options || [];
                },
                inputTypeForField(fieldKey) {
                    const type = this.fieldMeta(fieldKey)?.type || 'string';
                    if (type === 'number') {
                        return 'number';
                    }
                    if (type === 'date') {
                        return 'date';
                    }
                    if (type === 'datetime') {
                        return 'datetime-local';
                    }
                    return 'text';
                },
                formatOptionLabel(value) {
                    return String(value).replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
                },
                addFilter() {
                    this.filters.push({
                        field: '',
                        operator: '=',
                        value: '',
                        value_to: '',
                        boolean: 'and',
                    });
                },
                removeFilter(index) {
                    this.filters.splice(index, 1);
                },
            };
        }
    </script>
@endsection
