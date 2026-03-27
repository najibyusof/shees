@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Start Inspection" subtitle="Create an inspection draft from a checklist template." />
@endsection

@section('content')
    <x-ui.card title="Inspection Setup">
        <form method="POST" action="{{ route('inspections.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf

            <div class="sm:col-span-2">
                <label for="inspection_checklist_id" class="mb-1.5 block text-sm font-medium ui-text-muted">Checklist</label>
                <select id="inspection_checklist_id" name="inspection_checklist_id" required
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                    <option value="">Select checklist</option>
                    @foreach ($checklists as $checklist)
                        <option value="{{ $checklist->id }}" @selected((string) old('inspection_checklist_id', request('inspection_checklist_id')) === (string) $checklist->id)>
                            {{ $checklist->titleForLocale() }} (v{{ $checklist->version }})
                        </option>
                    @endforeach
                </select>
                @error('inspection_checklist_id')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.form-input name="location" label="Location" :value="old('location')" />
            <x-ui.form-input name="device_identifier" label="Device Identifier" :value="old('device_identifier')" />
            <x-ui.form-input name="offline_reference" label="Offline Reference" :value="old('offline_reference')" />

            <div class="sm:col-span-2">
                <label for="notes" class="mb-1.5 block text-sm font-medium ui-text-muted">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="sm:col-span-2 flex justify-end gap-2">
                <x-ui.button :href="route('inspections.index')" variant="secondary" size="md">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary" size="md">Create Draft Inspection</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
