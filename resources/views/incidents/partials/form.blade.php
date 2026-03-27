@props([
    'incident' => null,
    'classifications' => [],
    'submitLabel' => 'Save Incident',
])

<div class="space-y-6">
    <x-ui.card title="Incident Details" subtitle="Capture complete context for reporting and investigation.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.form-input name="title" label="Title" :value="old('title', $incident?->title)" required class="sm:col-span-2" />

            <x-ui.form-input name="location" label="Location" :value="old('location', $incident?->location)" required />

            <x-ui.form-input name="datetime" type="datetime-local" label="Date & Time" :value="old('datetime', $incident?->datetime?->format('Y-m-d\\TH:i'))" required />

            <div>
                <label for="classification"
                    class="mb-1.5 block text-sm font-medium ui-text-muted">Classification</label>
                <select id="classification" name="classification" required
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                    <option value="">Select classification</option>
                    @foreach ($classifications as $classification)
                        <option value="{{ $classification }}" @selected(old('classification', $incident?->classification) === $classification)>
                            {{ $classification }}
                        </option>
                    @endforeach
                </select>
                @error('classification')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="mb-1.5 block text-sm font-medium ui-text-muted">Description</label>
                <textarea id="description" name="description" rows="5" required
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('description', $incident?->description) }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="attachments" class="mb-1.5 block text-sm font-medium ui-text-muted">Attachments</label>
                <input id="attachments" name="attachments[]" type="file" multiple
                    accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                <p class="mt-1 text-xs ui-text-muted">Supported: JPG, PNG, GIF, WEBP, PDF, DOC, DOCX (max 10MB each).
                </p>
                @error('attachments')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                @error('attachments.*')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </x-ui.card>

    @if ($incident && $incident->attachments->count() > 0)
        <x-ui.card title="Existing Attachments" subtitle="Download or mark files to remove on update.">
            <div class="space-y-2">
                @foreach ($incident->attachments as $attachment)
                    <label class="flex items-center justify-between gap-3 rounded-lg border ui-border px-3 py-2">
                        <a href="{{ $attachment->url }}" target="_blank" class="text-sm ui-text hover:underline">
                            {{ $attachment->original_name }}
                        </a>

                        <span class="inline-flex items-center gap-2 text-sm ui-text-muted">
                            <input type="checkbox" name="remove_attachment_ids[]" value="{{ $attachment->id }}"
                                class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                            Remove
                        </span>
                    </label>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <div class="flex items-center justify-end gap-2">
        <x-ui.button :href="route('incidents.index')" variant="secondary" size="md">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary" size="md">{{ $submitLabel }}</x-ui.button>
    </div>
</div>
