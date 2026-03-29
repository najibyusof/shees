@props([
    'incident' => null,
    'submitLabel' => 'Save Incident',
])

@php
    $incidentId = $incident?->id;
    $isCreateMode = blank($incidentId);

    $autosaveCreateUrl = $autosaveCreateUrl ?? null;
    $autosaveUpdateTemplate = $autosaveUpdateTemplate ?? null;
    $updateActionTemplate = $updateActionTemplate ?? null;
    $actionUrl = $actionUrl ?? '';
    $formMethod = $formMethod ?? 'POST';

    $state = [
        'chronologies' => old(
            'chronologies',
            $incident?->chronologies
                ?->map(
                    fn($entry) => [
                        'id' => $entry->id,
                        'event_date' => $entry->event_date?->toDateString(),
                        'event_time' => $entry->event_time?->format('H:i'),
                        'events' => $entry->events,
                        'sort_order' => $entry->sort_order,
                    ],
                )
                ->values()
                ->toArray() ?? [['event_date' => '', 'event_time' => '', 'events' => '', 'sort_order' => 0]],
        ),
        'victims' => old(
            'victims',
            $incident?->victims
                ?->map(
                    fn($victim) => [
                        'id' => $victim->id,
                        'victim_type_id' => (string) $victim->victim_type_id,
                        'name' => $victim->name,
                        'identification' => $victim->identification,
                        'occupation' => $victim->occupation,
                        'age' => $victim->age,
                        'nationality' => $victim->nationality,
                        'working_experience' => $victim->working_experience,
                        'nature_of_injury' => $victim->nature_of_injury,
                        'body_injured' => $victim->body_injured,
                        'treatment' => $victim->treatment,
                    ],
                )
                ->values()
                ->toArray() ?? [
                [
                    'victim_type_id' => '',
                    'name' => '',
                    'identification' => '',
                    'occupation' => '',
                    'age' => '',
                    'nationality' => '',
                    'working_experience' => '',
                    'nature_of_injury' => '',
                    'body_injured' => '',
                    'treatment' => '',
                ],
            ],
        ),
        'witnesses' => old(
            'witnesses',
            $incident?->witnesses
                ?->map(
                    fn($witness) => [
                        'id' => $witness->id,
                        'name' => $witness->name,
                        'designation' => $witness->designation,
                        'identification' => $witness->identification,
                    ],
                )
                ->values()
                ->toArray() ?? [['name' => '', 'designation' => '', 'identification' => '']],
        ),
        'team' => old(
            'investigation_team_members',
            $incident?->investigationTeamMembers
                ?->map(
                    fn($member) => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'designation' => $member->designation,
                        'contact_number' => $member->contact_number,
                        'company' => $member->company,
                    ],
                )
                ->values()
                ->toArray() ?? [['name' => '', 'designation' => '', 'contact_number' => '', 'company' => '']],
        ),
        'damages' => old(
            'damages',
            $incident?->damages
                ?->map(
                    fn($damage) => [
                        'id' => $damage->id,
                        'damage_type_id' => (string) $damage->damage_type_id,
                        'estimate_cost' => $damage->estimate_cost,
                    ],
                )
                ->values()
                ->toArray() ?? [['damage_type_id' => '', 'estimate_cost' => '']],
        ),
        'immediateActions' => old(
            'immediate_actions',
            $incident?->immediateActions
                ?->map(
                    fn($action) => [
                        'id' => $action->id,
                        'action_taken' => $action->action_taken,
                        'company' => $action->company,
                    ],
                )
                ->values()
                ->toArray() ?? [['action_taken' => '', 'company' => '']],
        ),
        'plannedActions' => old(
            'planned_actions',
            $incident?->plannedActions
                ?->map(
                    fn($action) => [
                        'id' => $action->id,
                        'action_taken' => $action->action_taken,
                        'expected_date' => $action->expected_date?->toDateString(),
                        'actual_date' => $action->actual_date?->toDateString(),
                    ],
                )
                ->values()
                ->toArray() ?? [['action_taken' => '', 'expected_date' => '', 'actual_date' => '']],
        ),
        'attachments' => old('attachments', [
            ['attachment_type_id' => '', 'attachment_category_id' => '', 'description' => ''],
        ]),
        'immediateCauseIds' => array_map(
            'strval',
            old('immediate_cause_ids', $incident?->immediateCauses?->pluck('id')->all() ?? []),
        ),
        'contributingFactorIds' => array_map(
            'strval',
            old('contributing_factor_ids', $incident?->contributingFactors?->pluck('id')->all() ?? []),
        ),
        'workActivityIds' => array_map(
            'strval',
            old('work_activity_ids', $incident?->workActivities?->pluck('id')->all() ?? []),
        ),
        'externalPartyIds' => array_map(
            'strval',
            old('external_party_ids', $incident?->externalParties?->pluck('id')->all() ?? []),
        ),
    ];

    $config = [
        'incidentId' => $incidentId,
        'actionUrl' => $actionUrl,
        'method' => $formMethod,
        'createMode' => $isCreateMode,
        'autosaveCreateUrl' => $autosaveCreateUrl,
        'autosaveUpdateTemplate' => $autosaveUpdateTemplate,
        'updateActionTemplate' => $updateActionTemplate,
    ];
@endphp

<form id="incident-form" method="POST" :action="formAction" enctype="multipart/form-data" class="space-y-6"
    x-data="incidentWizard(@js($state), @js($config))" @input.passive="scheduleSave()" @change.passive="scheduleSave()">
    @csrf
    <input type="hidden" name="_method" :value="formMethod">
    <input type="hidden" name="temporary_id" x-model="temporaryId"
        value="{{ old('temporary_id', $incident?->temporary_id) }}">
    <input type="hidden" name="local_created_at" x-model="localCreatedAt"
        value="{{ old('local_created_at', $incident?->local_created_at?->toDateTimeString()) }}">

    <div x-show="draftRestored" x-cloak
        class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 px-4 py-2.5 text-sm text-amber-700 dark:text-amber-300">
        <div class="flex items-center justify-between gap-4">
            <span>Draft restored from a previous session.</span>
            <button type="button" @click="draftRestored = false; clearDraft()"
                class="text-xs underline hover:no-underline">Discard</button>
        </div>
    </div>

    @unless ($isCreateMode)
        <div class="rounded-2xl border ui-border ui-surface p-2 shadow-sm space-y-2">
            <div class="grid gap-2 sm:grid-cols-2">
                <button type="button" @click="setMainTab('notification')"
                    :class="activeMainTab === 'notification' ? 'border-indigo-500 text-indigo-700' :
                        'border-transparent text-slate-500 hover:text-slate-700'"
                    class="border-b-2 px-3 py-2.5 text-sm font-medium uppercase tracking-wide transition">
                    Notification
                </button>
                <button type="button" @click="setMainTab('investigation')"
                    :class="activeMainTab === 'investigation' ? 'border-indigo-500 text-indigo-700' :
                        'border-transparent text-slate-500 hover:text-slate-700'"
                    class="border-b-2 px-3 py-2.5 text-sm font-medium uppercase tracking-wide transition">
                    Investigation
                </button>
            </div>
            <div class="grid gap-2 sm:grid-cols-5" x-show="activeMainTab === 'investigation'" x-cloak>
                <button type="button" @click="setInvestigationTab('details')"
                    :class="investigationTab === 'details' ? 'border-indigo-500 text-indigo-700' :
                        'border-transparent text-slate-500 hover:text-slate-700'"
                    class="border-b-2 px-3 py-2 text-xs font-semibold uppercase tracking-wide transition">Details</button>
                <button type="button" @click="setInvestigationTab('impact')"
                    :class="investigationTab === 'impact' ? 'border-indigo-500 text-indigo-700' :
                        'border-transparent text-slate-500 hover:text-slate-700'"
                    class="border-b-2 px-3 py-2 text-xs font-semibold uppercase tracking-wide transition">Impact</button>
                <button type="button" @click="setInvestigationTab('causes')"
                    :class="investigationTab === 'causes' ? 'border-indigo-500 text-indigo-700' :
                        'border-transparent text-slate-500 hover:text-slate-700'"
                    class="border-b-2 px-3 py-2 text-xs font-semibold uppercase tracking-wide transition">Causes</button>
                <button type="button" @click="setInvestigationTab('closure')"
                    :class="investigationTab === 'closure' ? 'border-indigo-500 text-indigo-700' :
                        'border-transparent text-slate-500 hover:text-slate-700'"
                    class="border-b-2 px-3 py-2 text-xs font-semibold uppercase tracking-wide transition">Closure</button>
                <button type="button" @click="setInvestigationTab('attachment')"
                    :class="investigationTab === 'attachment' ? 'border-indigo-500 text-indigo-700' :
                        'border-transparent text-slate-500 hover:text-slate-700'"
                    class="border-b-2 px-3 py-2 text-xs font-semibold uppercase tracking-wide transition">Attachment</button>
            </div>
        </div>
    @endunless

    @if ($isCreateMode)
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-700">
            Submit only Notification details first. Investigation, causes, closure, and full attachments can be
            completed after submission from Edit Incident.
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Please correct the highlighted fields.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($isCreateMode)
        <section class="space-y-4">
            <x-ui.card title="General Information">
                <div class="grid gap-4 lg:grid-cols-2">

                    {{-- Incident Date --}}
                    <div>
                        <label for="incident_date" class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Incident Date <span class="text-red-500">*</span>
                        </label>
                        <input id="incident_date" name="incident_date" type="date" required
                            value="{{ old('incident_date', $incident?->incident_date?->toDateString() ?? $incident?->datetime?->toDateString()) }}"
                            @input="createTouched['incident_date'] = true"
                            :class="(createTouched['incident_date'] || submitAttempted) && createErrors['incident_date'] ?
                                '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-200">
                        <p x-show="(createTouched['incident_date'] || submitAttempted) && createErrors['incident_date']"
                            x-text="createErrors['incident_date'] || ''" class="mt-1 text-xs text-red-500" x-cloak></p>
                    </div>

                    {{-- Incident Time --}}
                    <div>
                        <label for="incident_time" class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Incident Time <span class="text-red-500">*</span>
                        </label>
                        <input id="incident_time" name="incident_time" type="time" required
                            value="{{ old('incident_time', $incident?->incident_time?->format('H:i') ?? $incident?->datetime?->format('H:i')) }}"
                            @input="createTouched['incident_time'] = true"
                            :class="(createTouched['incident_time'] || submitAttempted) && createErrors['incident_time'] ?
                                '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-200">
                        <p x-show="(createTouched['incident_time'] || submitAttempted) && createErrors['incident_time']"
                            x-text="createErrors['incident_time'] || ''" class="mt-1 text-xs text-red-500" x-cloak></p>
                    </div>

                    {{-- Incident Type --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Incident Type <span class="text-red-500">*</span>
                        </label>
                        <select name="incident_type_id" required @change="createTouched['incident_type_id'] = true"
                            :class="(createTouched['incident_type_id'] || submitAttempted) && createErrors['incident_type_id'] ?
                                '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select incident type</option>
                            @foreach ($incidentTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) old('incident_type_id', $incident?->incident_type_id) === (string) $type->id)>{{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        <p x-show="(createTouched['incident_type_id'] || submitAttempted) && createErrors['incident_type_id']"
                            x-text="createErrors['incident_type_id'] || ''" class="mt-1 text-xs text-red-500" x-cloak>
                        </p>
                    </div>

                    {{-- Work Package --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Work Package <span class="text-red-500">*</span>
                        </label>
                        <select name="work_package_id" required @change="createTouched['work_package_id'] = true"
                            :class="(createTouched['work_package_id'] || submitAttempted) && createErrors['work_package_id'] ?
                                '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select work package</option>
                            @foreach ($workPackages as $package)
                                <option value="{{ $package->id }}" @selected((string) old('work_package_id', $incident?->work_package_id) === (string) $package->id)>{{ $package->name }}
                                </option>
                            @endforeach
                        </select>
                        <p x-show="(createTouched['work_package_id'] || submitAttempted) && createErrors['work_package_id']"
                            x-text="createErrors['work_package_id'] || ''" class="mt-1 text-xs text-red-500" x-cloak>
                        </p>
                    </div>

                    {{-- Classification --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Classification <span class="text-red-500">*</span>
                        </label>
                        <select name="classification_id" required @change="createTouched['classification_id'] = true"
                            :class="(createTouched['classification_id'] || submitAttempted) && createErrors[
                                'classification_id'] ? '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select classification</option>
                            @foreach ($classifications as $classification)
                                <option value="{{ $classification->id }}" @selected((string) old('classification_id', $incident?->classification_id) === (string) $classification->id)>
                                    {{ $classification->name }}</option>
                            @endforeach
                        </select>
                        <p x-show="(createTouched['classification_id'] || submitAttempted) && createErrors['classification_id']"
                            x-text="createErrors['classification_id'] || ''" class="mt-1 text-xs text-red-500"
                            x-cloak></p>
                    </div>

                    {{-- Location Type --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Location Type <span class="text-red-500">*</span>
                        </label>
                        <select name="location_type_id" required x-model="selectedLocationTypeId"
                            @change="createTouched['location_type_id'] = true; createTouched['location'] = true"
                            :class="(createTouched['location_type_id'] || submitAttempted) && createErrors['location_type_id'] ?
                                '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select location type</option>
                            @foreach ($locationTypes ?? collect() as $locationType)
                                <option value="{{ $locationType->id }}" @selected((string) old('location_type_id', $incident?->location_type_id ?? ($incident?->incidentLocation?->location_type_id ?? '')) === (string) $locationType->id)>
                                    {{ $locationType->name }}</option>
                            @endforeach
                        </select>
                        <p x-show="(createTouched['location_type_id'] || submitAttempted) && createErrors['location_type_id']"
                            x-text="createErrors['location_type_id'] || ''" class="mt-1 text-xs text-red-500" x-cloak>
                        </p>
                    </div>

                    {{-- Location / Other Location --}}
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Location <span class="text-red-500">*</span>
                        </label>
                        <select name="location_id" x-model="selectedLocationId"
                            @change="createTouched['location'] = true"
                            :class="(createTouched['location'] || submitAttempted) && createErrors['location'] && !
                                selectedLocationId ? '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select location</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('location_id', $incident?->location_id) === (string) $location->id)>
                                    {{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Other Location</label>
                        <input type="text" name="other_location" x-model="manualOtherLocation"
                            :required="requiresOtherLocation"
                            value="{{ old('other_location', $incident?->other_location) }}"
                            @input="createTouched['location'] = true"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                        <p x-show="(createTouched['location'] || submitAttempted) && createErrors['location']"
                            x-text="createErrors['location'] || ''" class="mt-1 text-xs text-red-500" x-cloak></p>
                    </div>

                    {{-- Incident Title --}}
                    <div class="lg:col-span-2">
                        <label for="title" class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Incident Title <span class="text-red-500">*</span>
                        </label>
                        <input id="title" name="title" type="text" required
                            value="{{ old('title', $incident?->title) }}" @input="createTouched['title'] = true"
                            :class="(createTouched['title'] || submitAttempted) && createErrors['title'] ?
                                '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-200">
                        <p x-show="(createTouched['title'] || submitAttempted) && createErrors['title']"
                            x-text="createErrors['title'] || ''" class="mt-1 text-xs text-red-500" x-cloak></p>
                    </div>

                    {{-- Description --}}
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Brief description of incident <span class="text-red-500">*</span>
                        </label>
                        <textarea name="incident_description" rows="6" required @input="createTouched['incident_description'] = true"
                            :class="(createTouched['incident_description'] || submitAttempted) && createErrors[
                                'incident_description'] ? '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('incident_description', $incident?->incident_description ?? $incident?->description) }}</textarea>
                        <p x-show="(createTouched['incident_description'] || submitAttempted) && createErrors['incident_description']"
                            x-text="createErrors['incident_description'] || ''" class="mt-1 text-xs text-red-500"
                            x-cloak></p>
                    </div>

                    {{-- Immediate Response --}}
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">
                            Immediate Response / Action <span class="text-red-500">*</span>
                        </label>
                        <textarea name="immediate_response" rows="3" required @input="createTouched['immediate_response'] = true"
                            :class="(createTouched['immediate_response'] || submitAttempted) && createErrors[
                                'immediate_response'] ? '!border-red-400 dark:!border-red-500' : ''"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('immediate_response', $incident?->immediate_response) }}</textarea>
                        <p x-show="(createTouched['immediate_response'] || submitAttempted) && createErrors['immediate_response']"
                            x-text="createErrors['immediate_response'] || ''" class="mt-1 text-xs text-red-500"
                            x-cloak></p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Work Activity">
                <input type="hidden" name="work_activity_id" x-model="primaryWorkActivityId">
                <p class="mb-3 text-sm ui-text-muted">Select at least one work activity. <span
                        class="text-red-500">*</span></p>
                <div class="grid gap-x-8 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($workActivities as $activity)
                        <label class="flex items-center gap-2 text-sm ui-text-muted">
                            <input type="checkbox" name="work_activity_ids[]" value="{{ $activity->id }}"
                                x-model="workActivityIds"
                                @change="syncPrimaryWorkActivity(); createTouched['work_activity'] = true"
                                class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                            <span>{{ strtoupper($activity->name) }}</span>
                        </label>
                    @endforeach
                </div>
                <p x-show="(createTouched['work_activity'] || submitAttempted) && createErrors['work_activity']"
                    x-text="createErrors['work_activity'] || ''" class="mt-3 text-xs text-red-500" x-cloak></p>
            </x-ui.card>

            <x-ui.card title="Other Work Activity">
                <textarea name="activity_during_incident" rows="2"
                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('activity_during_incident', $incident?->activity_during_incident) }}</textarea>
            </x-ui.card>

            <x-ui.card title="Subcontractor Information">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Subcontractor</label>
                        <select name="subcontractor_id" x-model="selectedSubcontractorId"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select subcontractor</option>
                            @foreach ($subcontractors as $subcontractor)
                                <option value="{{ $subcontractor->id }}" @selected((string) old('subcontractor_id', $incident?->subcontractor_id) === (string) $subcontractor->id)>
                                    {{ $subcontractor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-ui.form-input name="person_in_charge" label="PIC" x-model="manualPersonInCharge"
                        :value="old('person_in_charge', $incident?->person_in_charge)" />
                    <x-ui.form-input name="subcontractor_contact_number" label="Contact Number"
                        x-model="manualSubcontractorContact" :value="old('subcontractor_contact_number', $incident?->subcontractor_contact_number)" />
                </div>
            </x-ui.card>

            <x-ui.card title="Attachment (Notification)"
                subtitle="At least one attachment is required for submission.">
                <div class="space-y-4">
                    <template x-for="(attachment, index) in attachments" :key="`notification-attachment-${index}`">
                        <div class="rounded-2xl border ui-border p-4">
                            <div class="grid gap-4 lg:grid-cols-4">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Attachment
                                        Type</label>
                                    <select :name="`attachments[${index}][attachment_type_id]`"
                                        x-model="attachment.attachment_type_id"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                        <option value="">Select type</option>
                                        @foreach ($attachmentTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Category</label>
                                    <select :name="`attachments[${index}][attachment_category_id]`"
                                        x-model="attachment.attachment_category_id"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                        <option value="">Select category</option>
                                        @foreach ($attachmentCategories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Description</label>
                                    <input type="text" :name="`attachments[${index}][description]`"
                                        x-model="attachment.description"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="lg:col-span-3">
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">File <template
                                            x-if="index === 0"><span class="text-red-500">*</span></template></label>
                                    <input type="file" :name="`attachments[${index}][file]`"
                                        :required="index === 0" accept=".jpg,.jpeg,.png,.pdf"
                                        @change="createTouched['attachment_file'] = true"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="flex items-end justify-end">
                                    <x-ui.button type="button" variant="danger" size="sm"
                                        @click="removeRow('attachments', index)">Remove</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <x-ui.button type="button" variant="secondary" size="md" @click="addAttachment">Add
                        Attachment Row</x-ui.button>
                    <p x-show="(createTouched['attachment_file'] || submitAttempted) && createErrors['attachment_file']"
                        x-text="createErrors['attachment_file'] || ''" class="text-xs text-red-500" x-cloak></p>
                    @error('attachments')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-ui.card>
        </section>
    @endif

    @unless ($isCreateMode)
        <section x-show="activeMainTab === 'notification'" x-cloak>
            <x-ui.card title="Notification" :subtitle="$isCreateMode
                ? 'Fill notification details for initial submission. Remaining sections are completed after submission.'
                : 'Edit submitted notification details before or during investigation.'">
                <div class="grid gap-4 lg:grid-cols-3">
                    <x-ui.form-input name="title" label="Incident Title" :value="old('title', $incident?->title)" required
                        class="lg:col-span-3" />

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Incident Type</label>
                        <select name="incident_type_id" required
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select type</option>
                            @foreach ($incidentTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) old('incident_type_id', $incident?->incident_type_id) === (string) $type->id)>{{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Classification</label>
                        <select name="classification_id" required
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select classification</option>
                            @foreach ($classifications as $classification)
                                <option value="{{ $classification->id }}" @selected((string) old('classification_id', $incident?->classification_id) === (string) $classification->id)>
                                    {{ $classification->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Reclassification</label>
                        <select name="reclassification_id"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select reclassification</option>
                            @foreach ($classifications as $classification)
                                <option value="{{ $classification->id }}" @selected((string) old('reclassification_id', $incident?->reclassification_id) === (string) $classification->id)>
                                    {{ $classification->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-ui.form-input name="incident_date" type="date" label="Incident Date" :value="old(
                        'incident_date',
                        $incident?->incident_date?->toDateString() ?? $incident?->datetime?->toDateString(),
                    )"
                        required />
                    <x-ui.form-input name="incident_time" type="time" label="Incident Time" :value="old(
                        'incident_time',
                        $incident?->incident_time?->format('H:i') ?? $incident?->datetime?->format('H:i'),
                    )"
                        required />

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Status</label>
                        <select name="status_id"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Use workflow default</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" @selected((string) old('status_id', $incident?->status_id) === (string) $status->id)>{{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Location Type</label>
                        <select name="location_type_id" required x-model="selectedLocationTypeId"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select location type</option>
                            @foreach ($locationTypes ?? collect() as $locationType)
                                <option value="{{ $locationType->id }}" @selected((string) old('location_type_id', $incident?->location_type_id ?? $incident?->incidentLocation?->location_type_id) === (string) $locationType->id)>
                                    {{ $locationType->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Location</label>
                        <select name="location_id" x-model="selectedLocationId"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select location</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('location_id', $incident?->location_id) === (string) $location->id)>{{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Other Location</label>
                        <input type="text" name="other_location" x-model="manualOtherLocation"
                            :required="requiresOtherLocation"
                            value="{{ old('other_location', $incident?->other_location) }}"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Work Package</label>
                        <select name="work_package_id" required
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select work package</option>
                            @foreach ($workPackages as $package)
                                <option value="{{ $package->id }}" @selected((string) old('work_package_id', $incident?->work_package_id) === (string) $package->id)>{{ $package->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Primary Work Activity</label>
                        <select name="work_activity_id" required x-model="primaryWorkActivityId"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select work activity</option>
                            @foreach ($workActivities as $activity)
                                <option value="{{ $activity->id }}" @selected((string) old('work_activity_id', $incident?->work_activity_id) === (string) $activity->id)>{{ $activity->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Subcontractor</label>
                        <select name="subcontractor_id" x-model="selectedSubcontractorId"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select subcontractor</option>
                            @foreach ($subcontractors as $subcontractor)
                                <option value="{{ $subcontractor->id }}" @selected((string) old('subcontractor_id', $incident?->subcontractor_id) === (string) $subcontractor->id)>
                                    {{ $subcontractor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-ui.form-input name="person_in_charge" label="Person In Charge" x-model="manualPersonInCharge"
                        :value="old('person_in_charge', $incident?->person_in_charge)" />
                    <x-ui.form-input name="subcontractor_contact_number" label="Subcontractor Contact Number"
                        x-model="manualSubcontractorContact" :value="old('subcontractor_contact_number', $incident?->subcontractor_contact_number)" />
                    <x-ui.form-input name="gps_location" label="GPS Location" :value="old('gps_location', $incident?->gps_location)" class="lg:col-span-2" />
                    <x-ui.form-input name="type_of_accident" label="Type of Accident" :value="old('type_of_accident', $incident?->type_of_accident)" />

                    <div class="lg:col-span-3">
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Incident Description</label>
                        <textarea name="incident_description" rows="5" required
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('incident_description', $incident?->incident_description ?? $incident?->description) }}</textarea>
                    </div>

                    <div class="lg:col-span-3">
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Immediate Response</label>
                        <textarea name="immediate_response" rows="3" required
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('immediate_response', $incident?->immediate_response) }}</textarea>
                    </div>

                    <div class="lg:col-span-3">
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Activity During Incident</label>
                        <textarea name="activity_during_incident" rows="3"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('activity_during_incident', $incident?->activity_during_incident) }}</textarea>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Root Cause</label>
                        <select name="rootcause_id"
                            class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            <option value="">Select root cause</option>
                            @foreach ($causeTypes as $cause)
                                <option value="{{ $cause->id }}" @selected((string) old('rootcause_id', $incident?->rootcause_id) === (string) $cause->id)>{{ $cause->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <x-ui.form-input name="other_rootcause" label="Other Root Cause" :value="old('other_rootcause', $incident?->other_rootcause)" />

                    <div class="lg:col-span-3 grid gap-4 lg:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium ui-text-muted">Basic Effect</label>
                            <textarea name="basic_effect" rows="3"
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('basic_effect', $incident?->basic_effect) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium ui-text-muted">Conclusion</label>
                            <textarea name="conclusion" rows="3"
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('conclusion', $incident?->conclusion) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium ui-text-muted">Close Remark</label>
                            <textarea name="close_remark" rows="3"
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">{{ old('close_remark', $incident?->close_remark) }}</textarea>
                        </div>
                    </div>
                </div>

                @if ($isCreateMode)
                    <div class="mt-6 border-t ui-border pt-4">
                        <x-ui.card title="Attachment (Notification)"
                            subtitle="At least one attachment is required for submission.">
                            <div class="space-y-4">
                                <template x-for="(attachment, index) in attachments"
                                    :key="`notification-attachment-${index}`">
                                    <div class="rounded-2xl border ui-border p-4">
                                        <div class="grid gap-4 lg:grid-cols-4">
                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium ui-text-muted">Attachment
                                                    Type</label>
                                                <select :name="`attachments[${index}][attachment_type_id]`"
                                                    x-model="attachment.attachment_type_id"
                                                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                                    <option value="">Select type</option>
                                                    @foreach ($attachmentTypes as $type)
                                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1.5 block text-sm font-medium ui-text-muted">Category</label>
                                                <select :name="`attachments[${index}][attachment_category_id]`"
                                                    x-model="attachment.attachment_category_id"
                                                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                                    <option value="">Select category</option>
                                                    @foreach ($attachmentCategories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="lg:col-span-2">
                                                <label
                                                    class="mb-1.5 block text-sm font-medium ui-text-muted">Description</label>
                                                <input type="text" :name="`attachments[${index}][description]`"
                                                    x-model="attachment.description"
                                                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                            </div>
                                            <div class="lg:col-span-3">
                                                <label class="mb-1.5 block text-sm font-medium ui-text-muted">File</label>
                                                <input type="file" :name="`attachments[${index}][file]`"
                                                    :required="isCreateMode && index === 0" accept=".jpg,.jpeg,.png,.pdf"
                                                    class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                            </div>
                                            <div class="flex items-end justify-end">
                                                <x-ui.button type="button" variant="danger" size="sm"
                                                    @click="removeRow('attachments', index)">Remove</x-ui.button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <x-ui.button type="button" variant="secondary" size="md"
                                    @click="addAttachment">Add Attachment Row</x-ui.button>
                                @error('attachments')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </x-ui.card>
                    </div>
                @endif
            </x-ui.card>
        </section>
    @endunless

    @unless ($isCreateMode)
        <section x-show="activeMainTab === 'investigation' && investigationTab === 'details'" x-cloak class="space-y-6">
            <x-ui.card title="Chronology" subtitle="Capture the sequence of events as a timeline dataset.">
                <div class="space-y-4">
                    <template x-for="(item, index) in chronologies" :key="`chronology-${index}`">
                        <div class="rounded-2xl border ui-border p-4">
                            <input type="hidden" :name="`chronologies[${index}][id]`" x-model="item.id">
                            <div class="grid gap-4 lg:grid-cols-4">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Event Date</label>
                                    <input type="date" :name="`chronologies[${index}][event_date]`"
                                        x-model="item.event_date"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Event Time</label>
                                    <input type="time" :name="`chronologies[${index}][event_time]`"
                                        x-model="item.event_time"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Sort Order</label>
                                    <input type="number" min="0" :name="`chronologies[${index}][sort_order]`"
                                        x-model="item.sort_order"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="flex items-end justify-end">
                                    <x-ui.button type="button" variant="danger" size="sm"
                                        @click="removeRow('chronologies', index)">Remove</x-ui.button>
                                </div>
                                <div class="lg:col-span-4">
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Narrative</label>
                                    <textarea :name="`chronologies[${index}][events]`" x-model="item.events" rows="3"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm"></textarea>
                                </div>
                            </div>
                        </div>
                    </template>
                    <x-ui.button type="button" variant="secondary" size="md" @click="addChronology">Add Timeline
                        Event</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card title="Investigation Team" subtitle="Assigned investigators and support members.">
                <div class="space-y-4">
                    <template x-for="(member, index) in team" :key="`team-${index}`">
                        <div class="rounded-2xl border ui-border p-4">
                            <input type="hidden" :name="`investigation_team_members[${index}][id]`" x-model="member.id">
                            <div class="grid gap-4 lg:grid-cols-5">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Name</label>
                                    <input type="text" :name="`investigation_team_members[${index}][name]`"
                                        x-model="member.name"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Designation</label>
                                    <input type="text" :name="`investigation_team_members[${index}][designation]`"
                                        x-model="member.designation"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Contact Number</label>
                                    <input type="text" :name="`investigation_team_members[${index}][contact_number]`"
                                        x-model="member.contact_number"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Company</label>
                                    <input type="text" :name="`investigation_team_members[${index}][company]`"
                                        x-model="member.company"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="flex items-end justify-end">
                                    <x-ui.button type="button" variant="danger" size="sm"
                                        @click="removeRow('team', index)">Remove</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <x-ui.button type="button" variant="secondary" size="md" @click="addTeamMember">Add Team
                        Member</x-ui.button>
                </div>
            </x-ui.card>
        </section>

        <section x-show="activeMainTab === 'investigation' && investigationTab === 'impact'" x-cloak class="space-y-6">
            <x-ui.card title="Victims Details" subtitle="Affected people and injury details.">
                <div class="space-y-4">
                    <template x-for="(victim, index) in victims" :key="`victim-${index}`">
                        <div class="rounded-2xl border ui-border p-4">
                            <input type="hidden" :name="`victims[${index}][id]`" x-model="victim.id">
                            <div class="grid gap-4 lg:grid-cols-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Victim Type</label>
                                    <select :name="`victims[${index}][victim_type_id]`" x-model="victim.victim_type_id"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                        <option value="">Select type</option>
                                        @foreach ($victimTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Name</label>
                                    <input type="text" :name="`victims[${index}][name]`" x-model="victim.name"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Identification</label>
                                    <input type="text" :name="`victims[${index}][identification]`"
                                        x-model="victim.identification"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Occupation</label>
                                    <input type="text" :name="`victims[${index}][occupation]`"
                                        x-model="victim.occupation"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Age</label>
                                    <input type="number" min="0" :name="`victims[${index}][age]`"
                                        x-model="victim.age"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Nationality</label>
                                    <input type="text" :name="`victims[${index}][nationality]`"
                                        x-model="victim.nationality"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Working
                                        Experience</label>
                                    <input type="text" :name="`victims[${index}][working_experience]`"
                                        x-model="victim.working_experience"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Nature of Injury</label>
                                    <input type="text" :name="`victims[${index}][nature_of_injury]`"
                                        x-model="victim.nature_of_injury"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Body Injured</label>
                                    <input type="text" :name="`victims[${index}][body_injured]`"
                                        x-model="victim.body_injured"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="lg:col-span-3">
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Treatment</label>
                                    <textarea :name="`victims[${index}][treatment]`" x-model="victim.treatment" rows="2"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm"></textarea>
                                </div>
                                <div class="lg:col-span-3 flex justify-end">
                                    <x-ui.button type="button" variant="danger" size="sm"
                                        @click="removeRow('victims', index)">Remove Victim</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <x-ui.button type="button" variant="secondary" size="md" @click="addVictim">Add
                        Victim</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card title="Witness" subtitle="People who observed the incident or its aftermath.">
                <div class="space-y-4">
                    <template x-for="(witness, index) in witnesses" :key="`witness-${index}`">
                        <div class="rounded-2xl border ui-border p-4">
                            <input type="hidden" :name="`witnesses[${index}][id]`" x-model="witness.id">
                            <div class="grid gap-4 lg:grid-cols-4">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Name</label>
                                    <input type="text" :name="`witnesses[${index}][name]`" x-model="witness.name"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Designation</label>
                                    <input type="text" :name="`witnesses[${index}][designation]`"
                                        x-model="witness.designation"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Identification</label>
                                    <input type="text" :name="`witnesses[${index}][identification]`"
                                        x-model="witness.identification"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="flex items-end justify-end">
                                    <x-ui.button type="button" variant="danger" size="sm"
                                        @click="removeRow('witnesses', index)">Remove Witness</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <x-ui.button type="button" variant="secondary" size="md" @click="addWitness">Add
                        Witness</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card title="Damages Details" subtitle="Asset or financial impact estimates.">
                <div class="space-y-4">
                    <template x-for="(damage, index) in damages" :key="`damage-${index}`">
                        <div class="rounded-2xl border ui-border p-4">
                            <input type="hidden" :name="`damages[${index}][id]`" x-model="damage.id">
                            <div class="grid gap-4 lg:grid-cols-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Damage Type</label>
                                    <select :name="`damages[${index}][damage_type_id]`" x-model="damage.damage_type_id"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                        <option value="">Select type</option>
                                        @foreach ($damageTypes as $damageType)
                                            <option value="{{ $damageType->id }}">{{ $damageType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Estimated Cost</label>
                                    <input type="number" step="0.01" min="0"
                                        :name="`damages[${index}][estimate_cost]`" x-model="damage.estimate_cost"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="flex items-end justify-end">
                                    <x-ui.button type="button" variant="danger" size="sm"
                                        @click="removeRow('damages', index)">Remove</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <x-ui.button type="button" variant="secondary" size="md" @click="addDamage">Add
                        Damage</x-ui.button>
                </div>
            </x-ui.card>
        </section>

        <section x-show="activeMainTab === 'investigation' && investigationTab === 'causes'" x-cloak>
            <x-ui.card title="Immediate Cause"
                subtitle="Assign causes, factors, related activities, and involved parties.">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Immediate Causes</label>
                        <select name="immediate_cause_ids[]" multiple x-model="immediateCauseIds"
                            class="min-h-40 w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            @foreach ($causeTypes as $cause)
                                <option value="{{ $cause->id }}">{{ $cause->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Contributing Factors</label>
                        <select name="contributing_factor_ids[]" multiple x-model="contributingFactorIds"
                            class="min-h-40 w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            @foreach ($factorTypes as $factor)
                                <option value="{{ $factor->id }}">{{ $factor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Work Activities</label>
                        <select name="work_activity_ids[]" multiple x-model="workActivityIds"
                            class="min-h-40 w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            @foreach ($workActivities as $activity)
                                <option value="{{ $activity->id }}">{{ $activity->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium ui-text-muted">Involved External Parties</label>
                        <select name="external_party_ids[]" multiple x-model="externalPartyIds"
                            class="min-h-40 w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                            @foreach ($externalParties as $party)
                                <option value="{{ $party->id }}">{{ $party->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-ui.card>
        </section>

        <section x-show="activeMainTab === 'investigation' && investigationTab === 'closure'" x-cloak class="space-y-6">
            <x-ui.card title="Immediate Action Taken" subtitle="Containment and immediate response activities.">
                <div class="space-y-4">
                    <template x-for="(action, index) in immediateActions" :key="`immediate-${index}`">
                        <div class="rounded-2xl border ui-border p-4">
                            <input type="hidden" :name="`immediate_actions[${index}][id]`" x-model="action.id">
                            <div class="grid gap-4 lg:grid-cols-4">
                                <div class="lg:col-span-3">
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Action Taken</label>
                                    <textarea :name="`immediate_actions[${index}][action_taken]`" x-model="action.action_taken" rows="2"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm"></textarea>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Company</label>
                                    <input type="text" :name="`immediate_actions[${index}][company]`"
                                        x-model="action.company"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="lg:col-span-4 flex justify-end">
                                    <x-ui.button type="button" variant="danger" size="sm"
                                        @click="removeRow('immediateActions', index)">Remove Action</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <x-ui.button type="button" variant="secondary" size="md" @click="addImmediateAction">Add
                        Immediate Action</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card title="Corrective Action" subtitle="Expected and actual completion tracking.">
                <div class="space-y-4">
                    <template x-for="(action, index) in plannedActions" :key="`planned-${index}`">
                        <div class="rounded-2xl border ui-border p-4">
                            <input type="hidden" :name="`planned_actions[${index}][id]`" x-model="action.id">
                            <div class="grid gap-4 lg:grid-cols-4">
                                <div class="lg:col-span-2">
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Action Taken</label>
                                    <textarea :name="`planned_actions[${index}][action_taken]`" x-model="action.action_taken" rows="2"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm"></textarea>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Expected Date</label>
                                    <input type="date" :name="`planned_actions[${index}][expected_date]`"
                                        x-model="action.expected_date"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Actual Date</label>
                                    <input type="date" :name="`planned_actions[${index}][actual_date]`"
                                        x-model="action.actual_date"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="lg:col-span-4 flex justify-end">
                                    <x-ui.button type="button" variant="danger" size="sm"
                                        @click="removeRow('plannedActions', index)">Remove Planned Action</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <x-ui.button type="button" variant="secondary" size="md" @click="addPlannedAction">Add Planned
                        Action</x-ui.button>
                </div>
            </x-ui.card>
        </section>

        <section x-show="activeMainTab === 'investigation' && investigationTab === 'attachment'" x-cloak
            class="space-y-6">
            <x-ui.card title="Attachments List" subtitle="Upload evidence with metadata for downstream review.">
                <div class="space-y-4">
                    <template x-for="(attachment, index) in attachments" :key="`attachment-${index}`">
                        <div class="rounded-2xl border ui-border p-4">
                            <div class="grid gap-4 lg:grid-cols-4">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Attachment Type</label>
                                    <select :name="`attachments[${index}][attachment_type_id]`"
                                        x-model="attachment.attachment_type_id"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                        <option value="">Select type</option>
                                        @foreach ($attachmentTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Category</label>
                                    <select :name="`attachments[${index}][attachment_category_id]`"
                                        x-model="attachment.attachment_category_id"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                        <option value="">Select category</option>
                                        @foreach ($attachmentCategories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">Description</label>
                                    <input type="text" :name="`attachments[${index}][description]`"
                                        x-model="attachment.description"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="lg:col-span-3">
                                    <label class="mb-1.5 block text-sm font-medium ui-text-muted">File</label>
                                    <input type="file" :name="`attachments[${index}][file]`"
                                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.mp4,.mov"
                                        class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                </div>
                                <div class="flex items-end justify-end">
                                    <x-ui.button type="button" variant="danger" size="sm"
                                        @click="removeRow('attachments', index)">Remove</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <x-ui.button type="button" variant="secondary" size="md" @click="addAttachment">Add Attachment
                        Row</x-ui.button>
                </div>
            </x-ui.card>

            @if ($incident && $incident->attachments->count() > 0)
                <x-ui.card title="Existing Attachments"
                    subtitle="Review uploaded evidence and mark files to remove on update.">
                    <div class="space-y-3">
                        @foreach ($incident->attachments as $attachment)
                            <label
                                class="flex flex-col gap-3 rounded-2xl border ui-border p-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <a href="{{ $attachment->url }}" target="_blank"
                                        class="text-sm font-medium ui-text hover:underline">{{ $attachment->filename ?? $attachment->original_name }}</a>
                                    <p class="mt-1 text-xs ui-text-muted">
                                        {{ $attachment->attachmentType?->name ?? 'Type N/A' }} •
                                        {{ $attachment->attachmentCategory?->name ?? 'Category N/A' }}</p>
                                    @if ($attachment->description)
                                        <p class="mt-1 text-sm ui-text-muted">{{ $attachment->description }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center gap-2 text-sm ui-text-muted">
                                    <input type="checkbox" name="remove_attachment_ids[]" value="{{ $attachment->id }}"
                                        class="rounded border-slate-300 dark:border-gray-600 dark:bg-gray-800">
                                    Remove on save
                                </span>
                            </label>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif
        </section>
    @endunless

    {{-- ── Navigation footer ───────────────────────────────────────────── --}}
    <div class="rounded-2xl border ui-border ui-surface p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">

            {{-- Auto-save status --}}
            <div class="flex items-center gap-2 text-xs ui-text-muted min-w-0">
                <template x-if="isSaving">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 animate-spin text-teal-500" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Saving draft…
                    </span>
                </template>
                <template x-if="!isSaving && savedAt">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-teal-500" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Draft saved at <span x-text="savedAtLabel" class="font-medium"></span>
                    </span>
                </template>
                <template x-if="!isSaving && !savedAt && autosaveCreateUrl">
                    <span>Changes auto-save as you type</span>
                </template>
            </div>

            {{-- Navigation buttons --}}
            <div class="flex items-center gap-2">
                <x-ui.button :href="route('incidents.index')" variant="secondary" size="md">Cancel</x-ui.button>
                {{-- Wrapper div captures clicks even when button is disabled, so all errors become visible --}}
                <div @click="if (isCreateMode) submitAttempted = true">
                    <button type="submit" :disabled="!canSubmit"
                        :class="!canSubmit ? 'opacity-50 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2">
                        {{ $submitLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    function incidentWizard(state, config) {
        return {
            // ── Layout mode ────────────────────────────────────────────────
            isCreateMode: Boolean(config.createMode),
            activeMainTab: 'notification',
            investigationTab: 'details',
            createTouched: {},
            submitAttempted: false,
            selectedLocationTypeId: @js((string) old('location_type_id', $incident?->location_type_id ?? ($incident?->incidentLocation?->location_type_id ?? ''))),
            selectedLocationId: @js((string) old('location_id', $incident?->location_id ?? '')),
            selectedSubcontractorId: @js((string) old('subcontractor_id', $incident?->subcontractor_id ?? '')),
            manualPersonInCharge: @js((string) old('person_in_charge', $incident?->person_in_charge ?? '')),
            manualSubcontractorContact: @js((string) old('subcontractor_contact_number', $incident?->subcontractor_contact_number ?? '')),
            primaryWorkActivityId: @js((string) old('work_activity_id', $incident?->work_activity_id ?? '')),

            // ── Auto-save state ────────────────────────────────────────────
            isSaving: false,
            savedAt: null,
            draftRestored: false,
            autoSaveTimer: null,
            incidentId: config.incidentId || null,
            temporaryId: document.querySelector('input[name="temporary_id"]')?.value || '',
            localCreatedAt: document.querySelector('input[name="local_created_at"]')?.value || '',
            formAction: config.actionUrl || '',
            formMethod: config.method || 'POST',
            autosaveCreateUrl: config.autosaveCreateUrl || null,
            autosaveUpdateTemplate: config.autosaveUpdateTemplate || null,
            updateActionTemplate: config.updateActionTemplate || null,
            draftKey: 'incident_draft_' + (config.incidentId || 'new'),

            // ── Data collections ───────────────────────────────────────────
            chronologies: state.chronologies || [],
            victims: state.victims || [],
            witnesses: state.witnesses || [],
            team: state.team || [],
            damages: state.damages || [],
            immediateActions: state.immediateActions || [],
            plannedActions: state.plannedActions || [],
            attachments: state.attachments || [],
            immediateCauseIds: state.immediateCauseIds || [],
            contributingFactorIds: state.contributingFactorIds || [],
            workActivityIds: state.workActivityIds || [],
            externalPartyIds: state.externalPartyIds || [],

            // ── Lifecycle ─────────────────────────────────────────────────
            init() {
                if (!this.temporaryId && window.crypto?.randomUUID) {
                    this.temporaryId = window.crypto.randomUUID();
                }
                if (!this.localCreatedAt) {
                    this.localCreatedAt = new Date().toISOString().slice(0, 19).replace('T', ' ');
                }

                if (!this.isCreateMode) {
                    try {
                        const savedMain = localStorage.getItem('incident_edit_main_tab_' + (this.incidentId || 'new'));
                        const savedInvestigation = localStorage.getItem('incident_edit_investigation_tab_' + (this
                            .incidentId || 'new'));
                        if (savedMain === 'notification' || savedMain === 'investigation') {
                            this.activeMainTab = savedMain;
                        }
                        if (['details', 'impact', 'causes', 'closure', 'attachment'].includes(savedInvestigation)) {
                            this.investigationTab = savedInvestigation;
                        }
                    } catch (_) {}
                }

                if (!this.incidentId) {
                    this.tryRestoreDraft();
                }

                if (this.primaryWorkActivityId && !this.workActivityIds.includes(this.primaryWorkActivityId)) {
                    this.workActivityIds.push(this.primaryWorkActivityId);
                }
                this.syncPrimaryWorkActivity();
            },
            setMainTab(tab) {
                this.activeMainTab = tab;
                try {
                    localStorage.setItem('incident_edit_main_tab_' + (this.incidentId || 'new'), tab);
                } catch (_) {}
            },
            setInvestigationTab(tab) {
                this.investigationTab = tab;
                try {
                    localStorage.setItem('incident_edit_investigation_tab_' + (this.incidentId || 'new'), tab);
                } catch (_) {}
            },
            get requiresOtherLocation() {
                return this.selectedLocationTypeId !== '' && this.selectedLocationId === '';
            },
            get requiresLocationSelection() {
                return this.selectedLocationTypeId !== '' && this.manualOtherLocation.trim() === '';
            },
            get manualOtherLocation() {
                return (document.querySelector('input[name="other_location"]')?.value || '').trim();
            },
            get requiresSubcontractorSelection() {
                return this.manualPersonInCharge.trim() === '' || this.manualSubcontractorContact.trim() === '';
            },
            get requiresManualSubcontractorDetails() {
                return this.selectedSubcontractorId === '';
            },
            get hasAnyAttachmentFile() {
                const fileInputs = Array.from(document.querySelectorAll(
                    'input[type="file"][name^="attachments["]'));
                return fileInputs.some((input) => input.files && input.files.length > 0);
            },
            get createErrors() {
                if (!this.isCreateMode) return {};
                const e = {};
                const v = (sel) => (document.querySelector(sel)?.value || '').trim();

                if (!v('input[name="incident_date"]')) e.incident_date = 'Incident date is required.';
                if (!v('input[name="incident_time"]')) e.incident_time = 'Incident time is required.';
                if (!v('select[name="incident_type_id"]')) e.incident_type_id = 'Incident type is required.';
                if (!v('select[name="work_package_id"]')) e.work_package_id = 'Work package is required.';
                if (!v('select[name="classification_id"]')) e.classification_id = 'Classification is required.';
                if (!v('select[name="location_type_id"]')) e.location_type_id = 'Location type is required.';
                if (!v('input[name="title"]')) e.title = 'Incident title is required.';
                if (!v('textarea[name="incident_description"]')) e.incident_description =
                'Description is required.';
                if (!v('textarea[name="immediate_response"]')) e.immediate_response =
                    'Immediate response is required.';

                const hasLocation = v('select[name="location_id"]') !== '' || v('input[name="other_location"]') !==
                    '';
                if (!hasLocation) e.location = 'Please select a location or enter an other location.';

                if (!this.primaryWorkActivityId) e.work_activity = 'Select at least one work activity.';

                if (!this.hasAnyAttachmentFile) e.attachment_file = 'At least one attachment file is required.';

                return e;
            },

            get canSubmit() {
                if (!this.isCreateMode) return true;
                return Object.keys(this.createErrors).length === 0;
            },

            submitForm() {
                this.submitAttempted = true;
                return Object.keys(this.createErrors).length === 0;
            },

            // ── Auto-save ─────────────────────────────────────────────────
            scheduleSave() {
                if (!this.autosaveCreateUrl) return;
                clearTimeout(this.autoSaveTimer);
                this.autoSaveTimer = setTimeout(() => this.triggerSave(), 3000);
            },
            scheduleImmediateSave() {
                if (!this.autosaveCreateUrl) return;
                clearTimeout(this.autoSaveTimer);
                this.autoSaveTimer = setTimeout(() => this.triggerSave(), 400);
            },
            async triggerSave() {
                if (this.isSaving || !this.autosaveCreateUrl) return;
                this.isSaving = true;
                try {
                    const form = document.getElementById('incident-form');
                    const rawData = new FormData(form);
                    // Strip binary files — autosave is text-only
                    const data = new FormData();
                    rawData.forEach((val, key) => {
                        if (!(val instanceof File)) {
                            data.append(key, val);
                        }
                    });
                    const url = this.incidentId && this.autosaveUpdateTemplate ?
                        this.autosaveUpdateTemplate.replace(':id', this.incidentId) :
                        this.autosaveCreateUrl;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: data,
                    });
                    if (res.ok) {
                        const json = await res.json();
                        this.savedAt = new Date();
                        if (!this.incidentId && json.id) {
                            this.incidentId = json.id;
                            if (this.updateActionTemplate) {
                                this.formAction = this.updateActionTemplate.replace(':id', json.id);
                            }
                            this.formMethod = 'PUT';
                            this.draftKey = 'incident_draft_' + json.id;
                        }
                        this.saveDraftLocally();
                    }
                } catch (_) {
                    // Offline: persist locally so nothing is lost
                    this.saveDraftLocally();
                } finally {
                    this.isSaving = false;
                }
            },

            // ── LocalStorage draft ────────────────────────────────────────
            saveDraftLocally() {
                try {
                    const form = document.getElementById('incident-form');
                    const data = {};
                    const formData = new FormData(form);
                    formData.forEach((value, key) => {
                        if (value instanceof File) {
                            return;
                        }
                        if (Object.prototype.hasOwnProperty.call(data, key)) {
                            if (Array.isArray(data[key])) {
                                data[key].push(value);
                            } else {
                                data[key] = [data[key], value];
                            }
                        } else {
                            data[key] = value;
                        }
                    });

                    localStorage.setItem(this.draftKey, JSON.stringify({
                        incidentId: this.incidentId,
                        savedAt: new Date().toISOString(),
                        form: data,
                    }));
                } catch (_) {}
            },
            tryRestoreDraft() {
                try {
                    const raw = localStorage.getItem('incident_draft_new');
                    if (!raw) return;
                    const draft = JSON.parse(raw);
                    if (draft && draft.incidentId && this.updateActionTemplate) {
                        this.draftRestored = true;
                        this.incidentId = draft.incidentId;
                        if (this.updateActionTemplate) {
                            this.formAction = this.updateActionTemplate.replace(':id', draft.incidentId);
                        }
                        this.formMethod = 'PUT';
                    }

                    if (draft?.form) {
                        this.$nextTick(() => {
                            Object.entries(draft.form).forEach(([name, value]) => {
                                const elements = Array.from(document.querySelectorAll(
                                    `[name="${name}"]`));
                                if (!elements.length) return;

                                const values = Array.isArray(value) ? value : [value];
                                elements.forEach((el, index) => {
                                    const nextValue = values[index] ?? values[0] ?? '';
                                    if (el.type === 'checkbox' || el.type === 'radio') {
                                        el.checked = values.includes(el.value);
                                    } else {
                                        el.value = nextValue;
                                    }
                                    el.dispatchEvent(new Event('input', {
                                        bubbles: true
                                    }));
                                    el.dispatchEvent(new Event('change', {
                                        bubbles: true
                                    }));
                                });
                            });
                        });
                    }
                } catch (_) {}
            },
            clearDraft() {
                try {
                    localStorage.removeItem('incident_draft_new');
                    if (this.incidentId) localStorage.removeItem('incident_draft_' + this.incidentId);
                } catch (_) {}
            },

            // ── Computed ──────────────────────────────────────────────────
            get savedAtLabel() {
                if (!this.savedAt) return '';
                return this.savedAt.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            syncPrimaryWorkActivity() {
                if (!Array.isArray(this.workActivityIds)) {
                    this.workActivityIds = [];
                }

                const current = String(this.primaryWorkActivityId || '');
                if (current !== '' && this.workActivityIds.includes(current)) {
                    return;
                }

                this.primaryWorkActivityId = this.workActivityIds.length > 0 ? String(this.workActivityIds[0]) : '';
            },

            // ── Collection methods ────────────────────────────────────────
            addChronology() {
                this.chronologies.push({
                    event_date: '',
                    event_time: '',
                    events: '',
                    sort_order: this.chronologies.length
                });
            },
            addVictim() {
                this.victims.push({
                    victim_type_id: '',
                    name: '',
                    identification: '',
                    occupation: '',
                    age: '',
                    nationality: '',
                    working_experience: '',
                    nature_of_injury: '',
                    body_injured: '',
                    treatment: ''
                });
            },
            addWitness() {
                this.witnesses.push({
                    name: '',
                    designation: '',
                    identification: ''
                });
            },
            addTeamMember() {
                this.team.push({
                    name: '',
                    designation: '',
                    contact_number: '',
                    company: ''
                });
            },
            addDamage() {
                this.damages.push({
                    damage_type_id: '',
                    estimate_cost: ''
                });
            },
            addImmediateAction() {
                this.immediateActions.push({
                    action_taken: '',
                    company: ''
                });
            },
            addPlannedAction() {
                this.plannedActions.push({
                    action_taken: '',
                    expected_date: '',
                    actual_date: ''
                });
            },
            addAttachment() {
                this.attachments.push({
                    attachment_type_id: '',
                    attachment_category_id: '',
                    description: ''
                });
            },
            removeRow(collection, index) {
                this[collection].splice(index, 1);
                if (!this[collection].length) {
                    if (collection === 'chronologies') this.addChronology();
                    if (collection === 'victims') this.addVictim();
                    if (collection === 'witnesses') this.addWitness();
                    if (collection === 'team') this.addTeamMember();
                    if (collection === 'damages') this.addDamage();
                    if (collection === 'immediateActions') this.addImmediateAction();
                    if (collection === 'plannedActions') this.addPlannedAction();
                    if (collection === 'attachments') this.addAttachment();
                }
            },
        };
    }
</script>
