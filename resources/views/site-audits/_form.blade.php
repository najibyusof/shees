@csrf

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium ui-text-muted" for="site_name">Site Name</label>
        <input id="site_name" name="site_name" type="text" required value="{{ old('site_name', ($siteAudit ?? null)?->site_name ?? '') }}"
            class="mt-1 w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
    </div>

    <div>
        <label class="block text-sm font-medium ui-text-muted" for="area">Area</label>
        <input id="area" name="area" type="text" value="{{ old('area', ($siteAudit ?? null)?->area ?? '') }}"
            class="mt-1 w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
    </div>

    <div>
        <label class="block text-sm font-medium ui-text-muted" for="audit_type">Audit Type</label>
        <input id="audit_type" name="audit_type" type="text" value="{{ old('audit_type', ($siteAudit ?? null)?->audit_type ?? 'internal') }}"
            class="mt-1 w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
    </div>

    <div>
        <label class="block text-sm font-medium ui-text-muted" for="scheduled_for">Scheduled For</label>
        <input id="scheduled_for" name="scheduled_for" type="date" value="{{ old('scheduled_for', optional(($siteAudit ?? null)?->scheduled_for)->format('Y-m-d')) }}"
            class="mt-1 w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
    </div>

    <div>
        <label class="block text-sm font-medium ui-text-muted" for="conducted_at">Conducted At</label>
        <input id="conducted_at" name="conducted_at" type="datetime-local"
                value="{{ old('conducted_at', optional(($siteAudit ?? null)?->conducted_at)->format('Y-m-d\TH:i')) }}"
            class="mt-1 w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
    </div>

    <div>
        <label class="block text-sm font-medium ui-text-muted" for="status">Status</label>
        <select id="status" name="status" class="mt-1 w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
            @php
                $selectedStatus = old('status', ($siteAudit ?? null)?->status ?? 'draft');
            @endphp
            @foreach (['draft', 'scheduled', 'in_progress', 'rejected'] as $status)
                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4">
    <label class="block text-sm font-medium ui-text-muted" for="scope">Audit Scope</label>
    <textarea id="scope" name="scope" rows="3" class="mt-1 w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">{{ old('scope', ($siteAudit ?? null)?->scope ?? '') }}</textarea>
</div>

<div class="mt-4">
    <label class="block text-sm font-medium ui-text-muted" for="summary">Summary</label>
    <textarea id="summary" name="summary" rows="4" class="mt-1 w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">{{ old('summary', ($siteAudit ?? null)?->summary ?? '') }}</textarea>
</div>

<div class="mt-5 flex justify-end gap-2">
    <x-ui.button :href="route('site-audits.index')" variant="secondary" size="md">Cancel</x-ui.button>
    <x-ui.button type="submit" variant="primary" size="md">{{ $buttonLabel ?? 'Save Audit' }}</x-ui.button>
</div>
