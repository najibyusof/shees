@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="$incident->title"
        :subtitle="'Ref: ' . ($incident->incident_reference_number ?? 'N/A') . '  •  ' . ($incident->incidentType?->name ?? $incident->classification ?? 'Incident')">
        <x-slot:actions>
            <x-ui.button :href="route('incidents.index')" variant="secondary" size="md">Back to List</x-ui.button>
            @can('update', $incident)
                <x-ui.button :href="route('incidents.edit', $incident)" variant="primary" size="md">Edit Incident</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
@php
    $classificationLabel = $incident->incidentClassification?->name ?? $incident->classification ?? '—';
    $reclassificationLabel = $incident->reclassification?->name ?? '—';
    $locationLabel = $incident->incidentLocation?->name ?? $incident->location ?? '—';
    $subcontractorLabel = $incident->subcontractor?->name ?? '—';
    $workActivities = $incident->workActivities->pluck('name')->filter()->values();
    $immediateCauses = $incident->immediateCauses->pluck('name')->filter()->values();
    $contributingFactors = $incident->contributingFactors->pluck('name')->filter()->values();

    // Workflow tracker
    $workflowSteps    = \App\Models\Incident::WORKFLOW_STEPS;
    $currentStatus    = $incident->status ?? 'draft';
    $stepKeys         = array_keys($workflowSteps);
    $currentStepIndex = array_search($currentStatus, $stepKeys, true);

    // Comment type badge styles
    $commentTypeBadge = [
        'general'         => ['label' => 'General',         'css' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'],
        'clarification'   => ['label' => 'Clarification',   'css' => 'bg-amber-100 text-amber-700'],
        'action_required' => ['label' => 'Action Required', 'css' => 'bg-red-100 text-red-700'],
        'investigation'   => ['label' => 'Investigation',   'css' => 'bg-blue-100 text-blue-700'],
        'review'          => ['label' => 'Review',          'css' => 'bg-purple-100 text-purple-700'],
    ];
@endphp

<div class="space-y-6"
    x-data="{
        mainTab: 'notification',
        investigationTab: 'details',
        replyOpen: {},
        toggleReply(id) { this.replyOpen[id] = !this.replyOpen[id]; },
        setMainTab(nextTab) {
            this.mainTab = nextTab;
            try {
                localStorage.setItem('incident_show_main_tab_{{ $incident->id }}', nextTab);
            } catch (_) {}
        },
        setInvestigationTab(nextTab) {
            this.investigationTab = nextTab;
            try {
                localStorage.setItem('incident_show_investigation_tab_{{ $incident->id }}', nextTab);
            } catch (_) {}
        },
        init() {
            try {
                this.mainTab = localStorage.getItem('incident_show_main_tab_{{ $incident->id }}') || 'notification';
                this.investigationTab = localStorage.getItem('incident_show_investigation_tab_{{ $incident->id }}') || 'details';
            } catch (_) {}
        },
    }"
    x-init="init()">

    <div class="rounded-2xl border ui-border ui-surface shadow-sm">
        <div class="border-b ui-border px-4 py-4 sm:px-6">
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <p class="text-xs uppercase ui-text-muted">Reference</p>
                    <p class="mt-1 text-sm font-medium ui-text">{{ $incident->incident_reference_number ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Status</p>
                    <p class="mt-1 text-sm font-medium ui-text">{{ strtoupper($incident->status ?? 'draft') }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Incident Type</p>
                    <p class="mt-1 text-sm ui-text">{{ $incident->incidentType?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Classification</p>
                    <p class="mt-1 text-sm ui-text">{{ $classificationLabel }}</p>
                </div>
            </div>
        </div>

        <div class="border-t ui-border px-4 py-4 sm:px-6">
            <p class="text-xs font-semibold uppercase tracking-wide ui-text-muted">Workflow Tracker</p>
            <div class="mt-3 grid gap-3 md:grid-cols-7">
                @foreach ($workflowSteps as $key => $step)
                    @php
                        $stepIndex = array_search($key, $stepKeys, true);
                        $isCompleted = $currentStepIndex !== false && $stepIndex < $currentStepIndex;
                        $isCurrent = $key === $currentStatus;
                    @endphp
                    <div class="rounded-xl border px-3 py-3 {{ $isCurrent ? 'border-cyan-500 bg-cyan-50/70' : ($isCompleted ? 'border-emerald-400 bg-emerald-50/70' : 'ui-border') }}">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold {{ $isCurrent ? 'bg-cyan-500 text-white' : ($isCompleted ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600') }}">
                                {{ $isCompleted ? '✓' : ($stepIndex + 1) }}
                            </span>
                            <p class="text-xs font-semibold uppercase tracking-wide ui-text">{{ $step['label'] }}</p>
                        </div>
                        <p class="mt-2 text-[11px] ui-text-muted">{{ $step['responsible'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="border-t ui-border px-4 py-4 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase ui-text-muted">Current Status</p>
                    <p class="mt-1 text-sm font-semibold ui-text">{{ \App\Services\IncidentWorkflowService::statusLabel($currentStatus) }}</p>
                </div>
                @can('transition', $incident)
                    @if (!empty($allowedTransitions))
                        <p class="text-xs ui-text-muted">Forward transitions only. Use remarks to provide context for reviewers.</p>
                    @endif
                @endcan
            </div>

            @can('transition', $incident)
                @if (!empty($allowedTransitions))
                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        @foreach ($allowedTransitions as $toStatus)
                            @php
                                $isBlocked = !empty($blockedTransitionReasons[$toStatus]);
                            @endphp
                            <form method="POST" action="{{ route('incidents.transition', $incident) }}" class="rounded-xl border ui-border p-4 {{ $isBlocked ? 'opacity-80' : '' }}">
                                @csrf
                                <input type="hidden" name="to_status" value="{{ $toStatus }}">
                                <p class="text-sm font-semibold ui-text">{{ \App\Services\IncidentWorkflowService::statusLabel($toStatus) }}</p>
                                <p class="mt-1 text-xs ui-text-muted">Move incident to this workflow stage.</p>
                                @if ($isBlocked)
                                    <p class="mt-2 text-xs font-medium text-amber-700">{{ $blockedTransitionReasons[$toStatus] }}</p>
                                @endif
                                <label class="mt-3 block text-xs font-medium uppercase tracking-wide ui-text-muted">Remarks (Optional)</label>
                                @if ($isBlocked)
                                    <textarea name="remarks" rows="2" class="mt-1 w-full rounded-lg border ui-border ui-surface px-3 py-2 text-sm ui-text shadow-sm" placeholder="Add transition notes for collaborators..." disabled></textarea>
                                @else
                                    <textarea name="remarks" rows="2" class="mt-1 w-full rounded-lg border ui-border ui-surface px-3 py-2 text-sm ui-text shadow-sm" placeholder="Add transition notes for collaborators..."></textarea>
                                @endif
                                <div class="mt-3">
                                    @if ($isBlocked)
                                        <x-ui.button type="submit" variant="primary" size="sm" disabled>Move to {{ \App\Services\IncidentWorkflowService::statusLabel($toStatus) }}</x-ui.button>
                                    @else
                                        <x-ui.button type="submit" variant="primary" size="sm">Move to {{ \App\Services\IncidentWorkflowService::statusLabel($toStatus) }}</x-ui.button>
                                    @endif
                                </div>
                            </form>
                        @endforeach
                    </div>
                @endif
            @endcan
        </div>

        <div class="px-4 py-4 sm:px-6">
            <div class="grid gap-2 sm:grid-cols-5">
                @foreach ([
                    'notification' => 'Notification',
                    'investigation' => 'Investigation',
                    'comments' => 'Comments (' . $incident->comments->count() . ')',
                    'workflow' => 'Workflow',
                    'log' => 'Log (' . $incident->activities->count() . ')',
                ] as $key => $label)
                    <button type="button" @click="setMainTab('{{ $key }}')"
                        :class="mainTab === '{{ $key }}' ? 'border-indigo-500 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-3 py-3 text-sm font-medium uppercase tracking-wide transition">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div x-show="mainTab === 'notification'" x-cloak class="space-y-6">
        <x-ui.card title="General Information" subtitle="Notification-stage capture based on the submitted incident report.">
            <div class="grid gap-6 lg:grid-cols-2">
                <div>
                    <p class="text-xs uppercase ui-text-muted">Incident Date</p>
                    <p class="mt-2 text-sm ui-text">{{ ($incident->incident_date ?? $incident->datetime)?->format('d-m-Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Incident Time</p>
                    <p class="mt-2 text-sm ui-text">{{ $incident->incident_time?->format('H:i:s') ?? $incident->datetime?->format('H:i:s') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Incident Type</p>
                    <p class="mt-2 text-sm ui-text">{{ $incident->incidentType?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Work Package</p>
                    <p class="mt-2 text-sm ui-text">{{ $incident->workPackage?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Classification</p>
                    <p class="mt-2 text-sm ui-text">{{ $classificationLabel }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Location Type</p>
                    <p class="mt-2 text-sm ui-text">{{ $incident->incidentLocation?->locationType?->name ?? 'Others' }}</p>
                </div>
                <div class="lg:col-span-2">
                    <p class="text-xs uppercase ui-text-muted">Location</p>
                    <p class="mt-2 text-sm ui-text">{{ $locationLabel }}</p>
                </div>
                <div class="lg:col-span-2 border-t ui-border pt-4">
                    <p class="text-xs uppercase ui-text-muted">Brief Description of Incident</p>
                    <p class="mt-2 whitespace-pre-line text-sm ui-text">{{ $incident->incident_description ?? $incident->description ?? '—' }}</p>
                </div>
                <div class="lg:col-span-2 border-t ui-border pt-4">
                    <p class="text-xs uppercase ui-text-muted">Immediate Response / Action</p>
                    <p class="mt-2 whitespace-pre-line text-sm ui-text">{{ $incident->immediate_response ?? '—' }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Work Activity" subtitle="Activities associated with the notification context.">
            @if ($workActivities->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($workActivities as $activity)
                        <div class="flex items-center gap-3 rounded-lg border ui-border px-3 py-2 text-sm ui-text">
                            <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm border border-teal-500 bg-teal-50 text-[10px] text-teal-700">✓</span>
                            <span>{{ strtoupper($activity) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Work Activities" description="No work activities were recorded for the notification stage." />
            @endif
        </x-ui.card>

        <x-ui.card title="Subcontractor Information" subtitle="Contractor and PIC details captured during notification.">
            <div class="grid gap-6 lg:grid-cols-3">
                <div>
                    <p class="text-xs uppercase ui-text-muted">Subcontractor</p>
                    <p class="mt-2 text-sm ui-text">{{ $subcontractorLabel }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">PIC</p>
                    <p class="mt-2 text-sm ui-text">{{ $incident->person_in_charge ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase ui-text-muted">Contact Number</p>
                    <p class="mt-2 text-sm ui-text">{{ $incident->subcontractor_contact_number ?? '—' }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Attachment (Notification)" subtitle="Submitted notification files and photographs.">
            @if ($incident->attachments->count() > 0)
                <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach ($incident->attachments as $attachment)
                        <div class="rounded-xl border ui-border p-4">
                            @if (str_starts_with($attachment->mime_type ?? '', 'image/'))
                                <a href="{{ $attachment->url }}" target="_blank" class="block overflow-hidden rounded-lg border ui-border">
                                    <img src="{{ $attachment->url }}" alt="{{ $attachment->filename ?? $attachment->original_name }}" class="h-48 w-full object-cover">
                                </a>
                            @else
                                <div class="flex h-32 items-center justify-center rounded-lg border ui-border bg-slate-50 text-rose-500">
                                    <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7V3h8l4 4v14H7a2 2 0 01-2-2V7h2z" />
                                    </svg>
                                </div>
                            @endif
                            <div class="mt-3 space-y-2 text-sm">
                                <p class="font-medium ui-text">{{ $attachment->filename ?? $attachment->original_name }}</p>
                                <p class="ui-text-muted">{{ $attachment->description ?? 'No description provided.' }}</p>
                                <a href="{{ $attachment->url }}" target="_blank" class="inline-flex rounded-lg bg-cyan-500 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-cyan-600">View</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Attachments" description="This incident has no notification attachments." />
            @endif
        </x-ui.card>
    </div>

    <div x-show="mainTab === 'investigation'" x-cloak class="space-y-6">
        <div class="rounded-2xl border ui-border ui-surface p-2 shadow-sm">
            <div class="grid gap-2 sm:grid-cols-5">
                @foreach ([
                    'details' => 'Details',
                    'impact' => 'Impact',
                    'causes' => 'Causes',
                    'attachment' => 'Attachment',
                    'closure' => 'Closure',
                ] as $key => $label)
                    <button type="button" @click="setInvestigationTab('{{ $key }}')"
                        :class="investigationTab === '{{ $key }}' ? 'border-indigo-500 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-3 py-3 text-sm font-medium uppercase tracking-wide transition">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div x-show="investigationTab === 'details'" x-cloak class="space-y-6">
            <x-ui.card title="Chronology" subtitle="Investigation timeline and sequence of events.">
                @if ($incident->chronologies->count() > 0)
                    <div class="overflow-hidden rounded-lg border ui-border">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-slate-100/80 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Time</th>
                                    <th class="px-4 py-3">Event</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y ui-border">
                                @foreach ($incident->chronologies as $entry)
                                    <tr>
                                        <td class="px-4 py-3 align-top ui-text">{{ $entry->event_date?->format('d-m-Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 align-top ui-text-muted">{{ $entry->event_time?->format('H:i:s') ?? '—' }}</td>
                                        <td class="px-4 py-3 align-top ui-text">{{ $entry->events }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state title="No Chronology" description="No chronology entries were recorded." />
                @endif
            </x-ui.card>

            <x-ui.card title="Investigation Team" subtitle="Team members involved in the investigation.">
                @if ($incident->investigationTeamMembers->count() > 0)
                    <div class="overflow-hidden rounded-lg border ui-border">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-slate-100/80 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Designation</th>
                                    <th class="px-4 py-3">Company</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y ui-border">
                                @foreach ($incident->investigationTeamMembers as $member)
                                    <tr>
                                        <td class="px-4 py-3 ui-text">{{ $member->name }}</td>
                                        <td class="px-4 py-3 ui-text-muted">{{ $member->designation ?? '—' }}</td>
                                        <td class="px-4 py-3 ui-text-muted">{{ $member->company ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state title="No Investigation Team" description="No team members were recorded." />
                @endif
            </x-ui.card>

            <x-ui.card title="Witness" subtitle="Witnesses available for the investigation.">
                @if ($incident->witnesses->count() > 0)
                    <div class="overflow-hidden rounded-lg border ui-border">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-slate-100/80 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Designation</th>
                                    <th class="px-4 py-3">Passport / MyKad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y ui-border">
                                @foreach ($incident->witnesses as $witness)
                                    <tr>
                                        <td class="px-4 py-3 ui-text">{{ $witness->name }}</td>
                                        <td class="px-4 py-3 ui-text-muted">{{ $witness->designation ?? '—' }}</td>
                                        <td class="px-4 py-3 ui-text-muted">{{ $witness->identification ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state title="No Record Found" description="No witness information has been recorded." />
                @endif
            </x-ui.card>

            <x-ui.card title="Classification" subtitle="Original and reviewed incident classification.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase ui-text-muted">Incident Classification</p>
                        <p class="mt-2 text-sm ui-text">{{ $classificationLabel }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase ui-text-muted">Incident ReClassification</p>
                        <p class="mt-2 text-sm ui-text">{{ $reclassificationLabel }}</p>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div x-show="investigationTab === 'impact'" x-cloak class="space-y-6">
            <x-ui.card title="Victims Details" subtitle="People directly impacted by the incident.">
                @if ($incident->victims->count() > 0)
                    <div class="space-y-3">
                        @foreach ($incident->victims as $victim)
                            <div class="rounded-lg border ui-border px-4 py-3">
                                <p class="font-medium ui-text">{{ $victim->name }}</p>
                                <p class="mt-1 text-sm ui-text-muted">
                                    {{ $victim->victimType?->name ?? 'Victim' }}
                                    @if ($victim->nature_of_injury)
                                        • {{ $victim->nature_of_injury }}
                                    @endif
                                    @if ($victim->body_injured)
                                        • {{ $victim->body_injured }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="No Record Found" description="No victims were recorded." />
                @endif
            </x-ui.card>

            <x-ui.card title="Damages Details" subtitle="Damage records and estimated cost.">
                @if ($incident->damages->count() > 0)
                    <div class="overflow-hidden rounded-lg border ui-border">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-slate-100/80 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3">Damage</th>
                                    <th class="px-4 py-3 text-right">Estimate Cost (RM)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y ui-border">
                                @foreach ($incident->damages as $damage)
                                    <tr>
                                        <td class="px-4 py-3 ui-text">{{ strtoupper($damage->damageType?->name ?? 'Unspecified Damage') }}</td>
                                        <td class="px-4 py-3 text-right ui-text">{{ number_format($damage->estimate_cost, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state title="No Damages" description="No damage records were captured." />
                @endif
            </x-ui.card>
        </div>

        <div x-show="investigationTab === 'causes'" x-cloak class="space-y-6">
            <x-ui.card title="Immediate Cause" subtitle="Immediate and contributing causes associated with the incident.">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase ui-text-muted">Immediate Cause</p>
                        @if ($immediateCauses->isNotEmpty())
                            <div class="mt-3 space-y-2">
                                @foreach ($immediateCauses as $cause)
                                    <div class="flex items-center gap-3 text-sm ui-text">
                                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm border border-slate-400 bg-slate-100 text-[10px]">■</span>
                                        <span>{{ strtoupper($cause) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-3 text-sm ui-text-muted">No immediate causes selected.</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs uppercase ui-text-muted">Unsafe Condition / Contributing Factor</p>
                        @if ($contributingFactors->isNotEmpty())
                            <div class="mt-3 space-y-2">
                                @foreach ($contributingFactors as $factor)
                                    <div class="flex items-center gap-3 text-sm ui-text">
                                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-sm border border-slate-400 bg-slate-100 text-[10px]">■</span>
                                        <span>{{ strtoupper($factor) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-3 text-sm ui-text-muted">No contributing factors selected.</p>
                        @endif
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Root Cause" subtitle="Reviewed root-cause determination.">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase ui-text-muted">Root Cause</p>
                        <p class="mt-2 text-sm ui-text">{{ $incident->rootCause?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase ui-text-muted">Other Root Cause</p>
                        <p class="mt-2 text-sm ui-text">{{ $incident->other_rootcause ?? '—' }}</p>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div x-show="investigationTab === 'attachment'" x-cloak>
            <x-ui.card title="Attachments List" subtitle="Investigation documents, meeting notes, and evidence.">
                @if ($incident->attachments->count() > 0)
                    <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                        @foreach ($incident->attachments as $attachment)
                            <div class="rounded-xl border ui-border p-4">
                                @if (str_starts_with($attachment->mime_type ?? '', 'image/'))
                                    <a href="{{ $attachment->url }}" target="_blank" class="block overflow-hidden rounded-lg border ui-border">
                                        <img src="{{ $attachment->url }}" alt="{{ $attachment->filename ?? $attachment->original_name }}" class="h-48 w-full object-cover">
                                    </a>
                                @else
                                    <div class="flex h-32 items-center justify-center rounded-lg border ui-border bg-slate-50 text-rose-500">
                                        <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7V3h8l4 4v14H7a2 2 0 01-2-2V7h2z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="mt-3 space-y-2 text-sm">
                                    <p class="font-medium ui-text">{{ $attachment->filename ?? $attachment->original_name }}</p>
                                    <p class="ui-text-muted">{{ $attachment->description ?? 'No description provided.' }}</p>
                                    <a href="{{ $attachment->url }}" target="_blank" class="inline-flex rounded-lg bg-cyan-500 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-cyan-600">View</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="No Investigation Attachments" description="No investigation attachments are available." />
                @endif
            </x-ui.card>
        </div>

        <div x-show="investigationTab === 'closure'" x-cloak class="space-y-6">
            <x-ui.card title="Immediate Action Taken" subtitle="Immediate containment and action history.">
                @if ($incident->immediateActions->count() > 0)
                    <div class="overflow-hidden rounded-lg border ui-border">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-slate-100/80 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Company</th>
                                    <th class="px-4 py-3">Action Taken</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y ui-border">
                                @foreach ($incident->immediateActions as $action)
                                    <tr>
                                        <td class="px-4 py-3 ui-text">{{ $incident->person_in_charge ?? $incident->reporter?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 ui-text-muted">{{ $action->company ?? $subcontractorLabel }}</td>
                                        <td class="px-4 py-3 ui-text">{{ $action->action_taken }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state title="No Immediate Actions" description="No closure-stage immediate actions were recorded." />
                @endif
            </x-ui.card>

            <x-ui.card title="Corrective Action" subtitle="Corrective and preventive actions for closure.">
                @if ($incident->plannedActions->count() > 0)
                    <div class="overflow-hidden rounded-lg border ui-border">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-slate-100/80 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Company</th>
                                    <th class="px-4 py-3">Action Taken</th>
                                    <th class="px-4 py-3">Expected Date</th>
                                    <th class="px-4 py-3">Actual Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y ui-border">
                                @foreach ($incident->plannedActions as $action)
                                    <tr>
                                        <td class="px-4 py-3 ui-text">{{ $incident->person_in_charge ?? $incident->reporter?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 ui-text-muted">{{ $subcontractorLabel }}</td>
                                        <td class="px-4 py-3 ui-text">{{ $action->action_taken }}</td>
                                        <td class="px-4 py-3 ui-text-muted">{{ $action->expected_date?->format('d-m-Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 ui-text-muted">{{ $action->actual_date?->format('d-m-Y') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state title="No Corrective Action" description="No corrective actions were recorded." />
                @endif
            </x-ui.card>

            <x-ui.card title="Incident Conclusion" subtitle="Investigation conclusion and closure remarks.">
                <div class="space-y-4 text-sm ui-text">
                    <p class="whitespace-pre-line">{{ $incident->conclusion ?? 'No conclusion recorded.' }}</p>
                    @if ($incident->close_remark)
                        <div class="border-t ui-border pt-4">
                            <p class="text-xs uppercase ui-text-muted">Close Remark</p>
                            <p class="mt-2 whitespace-pre-line">{{ $incident->close_remark }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>

    <div x-show="mainTab === 'comments'" x-cloak class="space-y-6">
        @can('comment', $incident)
            <x-ui.card title="Add Comment" subtitle="Use comments for clarification, action requests, and collaboration across workflow stages.">
                <form method="POST" action="{{ route('incidents.comment', $incident) }}" class="space-y-3">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium ui-text-muted">Comment Type</label>
                            <select name="comment_type" class="mt-1 w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                                <option value="general">General</option>
                                <option value="clarification">Clarification</option>
                                <option value="action_required">Action Required</option>
                                <option value="action">Action</option>
                                <option value="review">Review</option>
                                <option value="investigation">Investigation</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm ui-text">
                                <input type="checkbox" name="is_critical" value="1" class="h-4 w-4 rounded border ui-border" />
                                <span>Mark as critical issue</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <textarea name="comment" rows="4" required class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm" placeholder="Add your comment..."></textarea>
                        @error('comment')
                            <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center justify-end">
                        <x-ui.button type="submit" variant="primary" size="md">Post Comment</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        <x-ui.card title="Discussion Thread" subtitle="All workflow comments and replies in chronological order.">
            @if ($incident->comments->count() > 0)
                <div class="space-y-4">
                    @foreach ($incident->comments as $comment)
                        @php
                            $type = $comment->comment_type ?? 'general';
                            $badge = $commentTypeBadge[$type] ?? $commentTypeBadge['general'];
                            $isUnresolved = ! (bool) $comment->is_resolved;
                            $isCritical = (bool) $comment->is_critical;
                            $cardStateClass = $isUnresolved
                                ? ($isCritical ? 'border-amber-400 bg-amber-50/40' : 'border-rose-300 bg-rose-50/40')
                                : 'ui-border';
                        @endphp
                        <div class="overflow-hidden rounded-xl border {{ $cardStateClass }}">
                            <div class="px-4 py-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold ui-text">{{ $comment->user?->name ?? 'Unknown' }}</p>
                                        @if (!empty($comment->user?->roles?->pluck('name')?->first()))
                                            <p class="mt-1 text-xs ui-text-muted">{{ $comment->user->roles->pluck('name')->first() }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right space-y-1">
                                        <div class="space-x-1">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge['css'] }}">{{ $badge['label'] }}</span>
                                            @if ($isCritical)
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Critical</span>
                                            @endif
                                            @if ($isUnresolved)
                                                <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Unresolved</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Resolved</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs ui-text-muted">{{ $comment->created_at->format('d-m-Y H:i:s') }}</p>
                                    </div>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm ui-text">{{ $comment->comment }}</p>
                                @if ((bool) $comment->is_resolved)
                                    <p class="mt-2 text-xs text-emerald-700">
                                        Resolved by {{ $comment->resolver?->name ?? 'Unknown' }}
                                        @if ($comment->resolved_at)
                                            on {{ $comment->resolved_at->format('d-m-Y H:i:s') }}.
                                        @endif
                                    </p>
                                    @if (!empty($comment->resolution_note))
                                        <p class="mt-1 text-xs ui-text-muted">Note: {{ $comment->resolution_note }}</p>
                                    @endif
                                @endif
                            </div>

                            @if ($comment->replies->count() > 0)
                                <div class="border-t ui-border bg-slate-50/70 px-4 py-3">
                                    <button type="button" @click="toggleReply({{ $comment->id }})" class="text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:text-indigo-700">
                                        <span x-text="replyOpen[{{ $comment->id }}] ? 'Hide Replies' : 'Show Replies ({{ $comment->replies->count() }})'">Show Replies</span>
                                    </button>
                                    <div x-show="replyOpen[{{ $comment->id }}]" x-cloak class="mt-3 space-y-3">
                                        @foreach ($comment->replies as $reply)
                                            <div class="rounded-lg border ui-border bg-white px-3 py-3">
                                                <p class="text-sm font-medium ui-text">{{ $reply->user?->name ?? 'Unknown' }}</p>
                                                <p class="mt-1 text-sm ui-text">{{ $reply->reply }}</p>
                                                <p class="mt-2 text-xs ui-text-muted">{{ $reply->created_at->format('d-m-Y H:i:s') }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @can('comment', $incident)
                                <div class="border-t ui-border px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                                    <button type="button" @click="toggleReply('form_{{ $comment->id }}')" class="text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:text-indigo-700">
                                        <span x-text="replyOpen['form_{{ $comment->id }}'] ? 'Cancel Reply' : 'Reply'">Reply</span>
                                    </button>

                                    <form method="POST" action="{{ route('incidents.comment.resolve', [$incident, $comment]) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="resolved" value="{{ $comment->is_resolved ? '0' : '1' }}">
                                        @if (! $comment->is_resolved)
                                            <input type="text" name="resolution_note" placeholder="Resolution note (optional)" class="w-52 rounded-lg border ui-border ui-surface px-2 py-1 text-xs ui-text shadow-sm" />
                                        @endif
                                        <x-ui.button type="submit" variant="secondary" size="sm">
                                            {{ $comment->is_resolved ? 'Mark Unresolved' : 'Mark Resolved' }}
                                        </x-ui.button>
                                    </form>
                                </div>
                                <div x-show="replyOpen['form_{{ $comment->id }}']" x-cloak class="border-t ui-border bg-slate-50/70 px-4 py-3">
                                    <form method="POST" action="{{ route('incidents.comment.reply', [$incident, $comment]) }}" class="flex flex-col gap-3 sm:flex-row">
                                        @csrf
                                        <textarea name="reply" rows="2" required class="w-full rounded-lg border ui-border ui-surface px-3 py-2 text-sm ui-text shadow-sm" placeholder="Write your reply..."></textarea>
                                        <x-ui.button type="submit" variant="primary" size="sm">Post Reply</x-ui.button>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Comments Yet" description="Start the collaboration by posting a comment." />
            @endif
        </x-ui.card>
    </div>

    <div x-show="mainTab === 'workflow'" x-cloak class="space-y-6">
        <x-ui.card title="Workflow Log" subtitle="Role-based transition history for this incident.">
            @if ($incident->workflowLogs->count() > 0)
                <div class="space-y-3">
                    @foreach ($incident->workflowLogs as $entry)
                        <div class="rounded-xl border ui-border px-4 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold ui-text">{{ $entry->performer?->name ?? 'System' }}</p>
                                    <p class="mt-1 text-xs ui-text-muted">
                                        {{ \App\Services\IncidentWorkflowService::statusLabel($entry->from_status ?? 'draft') }}
                                        →
                                        {{ \App\Services\IncidentWorkflowService::statusLabel($entry->to_status ?? 'draft') }}
                                    </p>
                                    <p class="mt-2 text-sm ui-text">{{ $entry->action ?? 'Status transitioned' }}</p>
                                    @if (!empty($entry->remarks))
                                        <p class="mt-2 text-sm ui-text-muted">Remarks: {{ $entry->remarks }}</p>
                                    @endif
                                </div>
                                <p class="text-xs ui-text-muted">{{ optional($entry->created_at)->format('d-m-Y H:i:s') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Workflow Entries" description="No workflow transitions have been recorded yet." />
            @endif
        </x-ui.card>
    </div>

    <div x-show="mainTab === 'log'" x-cloak class="space-y-6">
        <x-ui.card title="Log" subtitle="System and workflow activity history.">
            @if ($incident->activities->count() > 0)
                <div class="space-y-3">
                    @foreach ($incident->activities as $activity)
                        <div class="rounded-xl border ui-border px-4 py-4">
                            <p class="text-sm font-semibold ui-text">{{ strtoupper($activity->user?->name ?? 'System') }}</p>
                            <p class="mt-2 text-sm ui-text">{{ strtoupper($activity->description ?? str_replace('_', ' ', $activity->action)) }}</p>
                            <p class="mt-3 text-xs ui-text-muted">{{ $activity->created_at->format('d-m-Y H:i:s') }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Log Entries" description="No log entries were recorded for this incident." />
            @endif
        </x-ui.card>
    </div>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button :href="route('incidents.index')" variant="secondary" size="md">Incident Alert</x-ui.button>
        <button type="button" onclick="window.print()" class="inline-flex items-center rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-600">Print</button>
    </div>
</div>
@endsection
