@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="'Inspection #' . $inspection->id" subtitle="Perform checklist responses and capture image evidence.">
        <x-slot:actions>
            <x-ui.button :href="route('inspections.index')" variant="secondary" size="md">Back</x-ui.button>
            @can('update', $inspection)
                @if ($inspection->status !== 'submitted')
                    <form method="POST" action="{{ route('inspections.submit', $inspection) }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary" size="md">Submit Inspection</x-ui.button>
                    </form>
                @endif
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <x-ui.card title="Inspection Metadata">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs uppercase ui-text-muted">Checklist</p>
                    <p class="mt-1 text-sm ui-text">{{ $inspection->checklist?->titleForLocale() ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Inspector</p>
                    <p class="mt-1 text-sm ui-text">{{ $inspection->inspector?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Status</p>
                    <p class="mt-1 text-sm ui-text">{{ $inspection->status }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Sync Status</p>
                    <p class="mt-1 text-sm ui-text">{{ $inspection->sync_status }}</p>
                </div>
            </div>
            <div class="mt-4 border-t ui-border pt-4">
                <p class="text-xs uppercase ui-text-muted">Offline UUID</p>
                <p class="mt-1 text-sm ui-text break-all">{{ $inspection->offline_uuid }}</p>
            </div>
        </x-ui.card>

        <x-ui.card title="Checklist Responses" subtitle="Capture findings. Responses are sync-ready for offline workflow.">
            <div class="space-y-4">
                @foreach ($inspection->responses as $response)
                    @php
                        $item = $response->checklistItem;
                        if (!$item) {
                            continue;
                        }
                    @endphp
                    <div class="rounded-lg border ui-border p-4">
                        @can('update', $inspection)
                            <form method="POST" action="{{ route('inspections.responses.update', $inspection) }}"
                                class="space-y-3">
                                @csrf
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium ui-text">{{ $item->labelForLocale() }}</p>
                                    <x-ui.badge :variant="$item->is_required ? 'warning' : 'neutral'">
                                        {{ $item->is_required ? 'Required' : 'Optional' }}
                                    </x-ui.badge>
                                </div>

                                @if ($item->item_type === 'boolean')
                                    <select name="responses[{{ $response->id }}][response_value]"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                        <option value="">Select</option>
                                        <option value="yes" @selected(old("responses.{$response->id}.response_value", $response->response_value) === 'yes')>Yes</option>
                                        <option value="no" @selected(old("responses.{$response->id}.response_value", $response->response_value) === 'no')>No</option>
                                    </select>
                                @elseif ($item->item_type === 'choice')
                                    <select name="responses[{{ $response->id }}][response_value]"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                        <option value="">Select option</option>
                                        @foreach ($item->options ?? [] as $option)
                                            <option value="{{ $option }}" @selected(old("responses.{$response->id}.response_value", $response->response_value) === $option)>
                                                {{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($item->item_type === 'number')
                                    <input type="number" name="responses[{{ $response->id }}][response_value]"
                                        value="{{ old("responses.{$response->id}.response_value", $response->response_value) }}"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                @else
                                    <textarea name="responses[{{ $response->id }}][response_value]" rows="2"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old("responses.{$response->id}.response_value", $response->response_value) }}</textarea>
                                @endif

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Comment</label>
                                        <textarea name="responses[{{ $response->id }}][comment]" rows="2"
                                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old("responses.{$response->id}.comment", $response->comment) }}</textarea>
                                    </div>
                                    <div class="flex items-end gap-3">
                                        <label class="inline-flex items-center gap-2 text-sm ui-text">
                                            <input type="checkbox" name="responses[{{ $response->id }}][is_non_compliant]"
                                                value="1" @checked(old("responses.{$response->id}.is_non_compliant", $response->is_non_compliant))
                                                class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                                            Mark non-compliant
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-sm ui-text">
                                            <input type="checkbox" name="mark_as_completed" value="1"
                                                class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                                            Complete
                                        </label>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <x-ui.button type="submit" variant="primary" size="sm">Save Response</x-ui.button>
                                </div>
                            </form>
                        @else
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="text-sm font-medium ui-text">{{ $item->labelForLocale() }}</p>
                                <x-ui.badge :variant="$item->is_required ? 'warning' : 'neutral'">
                                    {{ $item->is_required ? 'Required' : 'Optional' }}
                                </x-ui.badge>
                            </div>

                            <p class="text-sm ui-text-muted">You have read-only access to this inspection response.</p>
                        @endcan

                        <div class="mt-3 rounded-lg border ui-border ui-surface-soft p-3">
                            <p class="text-xs uppercase ui-text-muted">Evidence Images</p>

                            @if ($response->images->count() > 0)
                                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                    @foreach ($response->images as $image)
                                        <a href="{{ asset('storage/' . $image->file_path) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $image->file_path) }}"
                                                alt="{{ $image->original_name }}"
                                                class="h-24 w-full rounded-md object-cover">
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @can('update', $inspection)
                                <form method="POST"
                                    action="{{ route('inspections.responses.images.store', [$inspection, $response]) }}"
                                    enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-2">
                                    @csrf
                                    <input type="file" name="image" required accept=".jpg,.jpeg,.png,.webp"
                                        class="rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                    <input type="datetime-local" name="captured_at"
                                        class="rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                    <x-ui.button type="submit" variant="secondary" size="sm">Upload Image</x-ui.button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    </div>
@endsection
