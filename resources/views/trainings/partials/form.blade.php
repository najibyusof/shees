@props([
    'training' => null,
    'submitLabel' => 'Save Training',
])

<div class="space-y-6">
    <x-ui.card title="Training Details" subtitle="Define the training program and multilingual content.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.form-input name="title" label="Title (Default)" :value="old('title', $training?->title)" required class="sm:col-span-2" />

            <x-ui.form-input name="title_translations[en]" label="Title (English)" :value="old('title_translations.en', $training?->title_translations['en'] ?? '')" />
            <x-ui.form-input name="title_translations[id]" label="Title (Indonesian)" :value="old('title_translations.id', $training?->title_translations['id'] ?? '')" />

            <div class="sm:col-span-2">
                <label for="description" class="mb-1.5 block text-sm font-medium ui-text-muted">Description
                    (Default)</label>
                <textarea id="description" name="description" rows="3"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('description', $training?->description) }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description_translations_en"
                    class="mb-1.5 block text-sm font-medium ui-text-muted">Description (English)</label>
                <textarea id="description_translations_en" name="description_translations[en]" rows="3"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('description_translations.en', $training?->description_translations['en'] ?? '') }}</textarea>
            </div>

            <div>
                <label for="description_translations_id"
                    class="mb-1.5 block text-sm font-medium ui-text-muted">Description (Indonesian)</label>
                <textarea id="description_translations_id" name="description_translations[id]" rows="3"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('description_translations.id', $training?->description_translations['id'] ?? '') }}</textarea>
            </div>

            <x-ui.form-input name="starts_at" type="date" label="Start Date" :value="old('starts_at', optional($training?->starts_at)->format('Y-m-d'))" />
            <x-ui.form-input name="ends_at" type="date" label="End Date" :value="old('ends_at', optional($training?->ends_at)->format('Y-m-d'))" />

            <x-ui.form-input name="certificate_validity_days" type="number" label="Certificate Validity (days)"
                :value="old('certificate_validity_days', $training?->certificate_validity_days ?? 365)" required />

            <div class="flex items-center gap-2 rounded-lg border ui-border px-3 py-2.5">
                <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $training?->is_active ?? true))
                    class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                <label for="is_active" class="text-sm ui-text">Active Training</label>
            </div>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-2">
        <x-ui.button :href="route('trainings.index')" variant="secondary" size="md">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary" size="md">{{ $submitLabel }}</x-ui.button>
    </div>
</div>
