@props([
    'checklist' => null,
    'submitLabel' => 'Save Checklist',
])

@php
    $items = old(
        'items',
        $checklist?->items
            ?->map(function ($item) {
                return [
                    'label' => $item->label,
                    'label_translations' => $item->label_translations ?? [],
                    'item_type' => $item->item_type,
                    'options' => is_array($item->options ?? null) ? implode(',', $item->options) : '',
                    'is_required' => $item->is_required,
                    'sort_order' => $item->sort_order,
                ];
            })
            ->toArray() ?? [
            ['label' => '', 'item_type' => 'boolean', 'options' => '', 'is_required' => false, 'sort_order' => 0],
        ],
    );
@endphp

<div class="space-y-6" x-data="inspectionChecklistBuilder(@js($items))">
    <x-ui.card title="Checklist Information" subtitle="Template metadata with translation support.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.form-input name="code" label="Code" :value="old('code', $checklist?->code)" />
            <x-ui.form-input name="title" label="Title (Default)" :value="old('title', $checklist?->title)" required />

            <x-ui.form-input name="title_translations[en]" label="Title (English)" :value="old('title_translations.en', $checklist?->title_translations['en'] ?? '')" />
            <x-ui.form-input name="title_translations[id]" label="Title (Indonesian)" :value="old('title_translations.id', $checklist?->title_translations['id'] ?? '')" />

            <div class="sm:col-span-2">
                <label for="description" class="mb-1.5 block text-sm font-medium ui-text-muted">Description
                    (Default)</label>
                <textarea id="description" name="description" rows="3"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('description', $checklist?->description) }}</textarea>
            </div>

            <div>
                <label for="description_en" class="mb-1.5 block text-sm font-medium ui-text-muted">Description
                    (English)</label>
                <textarea id="description_en" name="description_translations[en]" rows="3"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('description_translations.en', $checklist?->description_translations['en'] ?? '') }}</textarea>
            </div>
            <div>
                <label for="description_id" class="mb-1.5 block text-sm font-medium ui-text-muted">Description
                    (Indonesian)</label>
                <textarea id="description_id" name="description_translations[id]" rows="3"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('description_translations.id', $checklist?->description_translations['id'] ?? '') }}</textarea>
            </div>

            <label class="inline-flex items-center gap-2 text-sm ui-text">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $checklist?->is_active ?? true))
                    class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                Active checklist
            </label>
        </div>
    </x-ui.card>

    <x-ui.card title="Checklist Builder" subtitle="Define checklist items used by inspection runs.">
        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="rounded-lg border ui-border p-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium ui-text-muted">Label</label>
                            <input type="text" :name="`items[${index}][label]`" x-model="item.label" required
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium ui-text-muted">Label (EN)</label>
                            <input type="text" :name="`items[${index}][label_translations][en]`"
                                x-model="item.labelTranslationsEn"
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium ui-text-muted">Label (ID)</label>
                            <input type="text" :name="`items[${index}][label_translations][id]`"
                                x-model="item.labelTranslationsId"
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium ui-text-muted">Type</label>
                            <select :name="`items[${index}][item_type]`" x-model="item.item_type"
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                <option value="boolean">Boolean</option>
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="choice">Choice</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium ui-text-muted">Options
                                (comma-separated)</label>
                            <input type="text" :name="`items[${index}][options]`" x-model="item.options"
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm"
                                placeholder="Only used for choice type">
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium ui-text-muted">Sort Order</label>
                            <input type="number" min="0" :name="`items[${index}][sort_order]`"
                                x-model="item.sort_order"
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center gap-2 text-sm ui-text">
                                <input type="checkbox" :name="`items[${index}][is_required]`" value="1"
                                    x-model="item.is_required" class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                                Required
                            </label>
                            <x-ui.button type="button" variant="danger" size="sm"
                                @click="removeItem(index)">Remove</x-ui.button>
                        </div>
                    </div>
                </div>
            </template>

            <x-ui.button type="button" variant="secondary" size="md" @click="addItem">Add Checklist
                Item</x-ui.button>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-2">
        <x-ui.button :href="route('inspection-checklists.index')" variant="secondary" size="md">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary" size="md">{{ $submitLabel }}</x-ui.button>
    </div>
</div>

<script>
    function inspectionChecklistBuilder(initialItems) {
        const normalized = (initialItems || []).map((item) => ({
            label: item.label || '',
            labelTranslationsEn: (item.label_translations || {}).en || '',
            labelTranslationsId: (item.label_translations || {}).id || '',
            item_type: item.item_type || 'boolean',
            options: item.options || '',
            is_required: !!item.is_required,
            sort_order: item.sort_order ?? 0,
        }));

        return {
            items: normalized.length ? normalized : [{
                label: '',
                labelTranslationsEn: '',
                labelTranslationsId: '',
                item_type: 'boolean',
                options: '',
                is_required: false,
                sort_order: 0,
            }],
            addItem() {
                this.items.push({
                    label: '',
                    labelTranslationsEn: '',
                    labelTranslationsId: '',
                    item_type: 'boolean',
                    options: '',
                    is_required: false,
                    sort_order: this.items.length,
                });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
        };
    }
</script>
