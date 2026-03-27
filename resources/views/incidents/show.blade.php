@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="$incident->title" subtitle="Incident details, timeline context, and attachment evidence.">
        <x-slot:actions>
            <x-ui.button :href="route('incidents.index')" variant="secondary" size="md">Back to List</x-ui.button>
            @can('update', $incident)
                <x-ui.button :href="route('incidents.edit', $incident)" variant="primary" size="md">Edit Incident</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <x-ui.card title="Overview">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs uppercase ui-text-muted">Classification</p>
                    <div class="mt-1">
                        <x-ui.badge :variant="match ($incident->classification) {
                            'Critical' => 'error',
                            'Major' => 'warning',
                            'Moderate' => 'info',
                            default => 'neutral',
                        }">
                            {{ $incident->classification }}
                        </x-ui.badge>
                    </div>
                </div>

                <div>
                    <p class="text-xs uppercase ui-text-muted">Status</p>
                    <div class="mt-1">
                        <x-ui.status-badge :status="$incident->status" />
                    </div>
                </div>

                <div>
                    <p class="text-xs uppercase ui-text-muted">Location</p>
                    <p class="mt-1 text-sm ui-text">{{ $incident->location }}</p>
                </div>

                <div>
                    <p class="text-xs uppercase ui-text-muted">Date & Time</p>
                    <p class="mt-1 text-sm ui-text">{{ $incident->datetime->format('Y-m-d H:i') }}</p>
                </div>
            </div>

            <div class="mt-5 border-t ui-border pt-4">
                <p class="text-xs uppercase ui-text-muted">Reported By</p>
                <p class="mt-1 text-sm ui-text">{{ $incident->reporter?->name }} ({{ $incident->reporter?->email }})</p>
            </div>

            <div class="mt-5 border-t ui-border pt-4">
                <p class="text-xs uppercase ui-text-muted">Approval Progress</p>
                <p class="mt-1 text-sm ui-text">
                    {{ count($approvedRoles ?? []) }} / {{ count($requiredApprovalRoles ?? []) }} required roles:
                    {{ implode(', ', $requiredApprovalRoles ?? []) }}
                </p>
                @if (!empty($missingApprovalRoles))
                    <p class="mt-1 text-xs ui-text-muted">
                        Remaining: {{ implode(', ', $missingApprovalRoles) }}
                    </p>
                @endif
            </div>

            @if ($incident->rejection_reason)
                <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 p-3 dark:border-rose-700 dark:bg-rose-900/30">
                    <p class="text-xs uppercase text-rose-700 dark:text-rose-300">Rejection Reason</p>
                    <p class="mt-1 text-sm text-rose-900 dark:text-rose-100">{{ $incident->rejection_reason }}</p>
                </div>
            @endif
        </x-ui.card>

        @canany(['submit', 'approve', 'reject', 'comment'], $incident)
            <x-ui.card title="Workflow Actions"
                subtitle="Submit, decide, and annotate this incident through approval workflow.">
                <div class="space-y-4">
                    @can('submit', $incident)
                        <form method="POST" action="{{ route('incidents.submit', $incident) }}">
                            @csrf
                            <x-ui.button type="submit" variant="primary" size="md">Submit for Approval</x-ui.button>
                        </form>
                    @endcan

                    @can('approve', $incident)
                        <form method="POST" action="{{ route('incidents.approve', $incident) }}" class="space-y-2">
                            @csrf
                            <label for="approve_remarks" class="block text-sm font-medium ui-text-muted">Approval Remarks
                                (optional)
                            </label>
                            <textarea id="approve_remarks" name="remarks" rows="2"
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm"
                                placeholder="Add optional approval remarks..."></textarea>
                            <x-ui.button type="submit" variant="primary" size="md">Approve Incident</x-ui.button>
                        </form>
                    @endcan

                    @can('reject', $incident)
                        <form method="POST" action="{{ route('incidents.reject', $incident) }}" class="space-y-2">
                            @csrf
                            <label for="reject_reason" class="block text-sm font-medium ui-text-muted">Rejection Reason</label>
                            <textarea id="reject_reason" name="reason" rows="2" required
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm"
                                placeholder="Provide rejection reason..."></textarea>
                            <x-ui.button type="submit" variant="danger" size="md">Reject Incident</x-ui.button>
                        </form>
                    @endcan

                    @can('comment', $incident)
                        <form method="POST" action="{{ route('incidents.comment', $incident) }}" class="space-y-2">
                            @csrf
                            <label for="workflow_comment" class="block text-sm font-medium ui-text-muted">Workflow Comment</label>
                            <textarea id="workflow_comment" name="comment" rows="3" required
                                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm"
                                placeholder="Add context, findings, or follow-up notes..."></textarea>
                            @error('comment')
                                <p class="text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                            <x-ui.button type="submit" variant="secondary" size="md">Add Comment</x-ui.button>
                        </form>
                    @endcan
                </div>
            </x-ui.card>
        @endcanany

        <x-ui.card title="Unified Workflow History"
            subtitle="Combined timeline of activities, comments, and approval decisions.">
            @if (($workflowHistory ?? collect())->count() > 0)
                <div class="space-y-3">
                    @foreach ($workflowHistory as $entry)
                        <div class="rounded-xl border ui-border ui-surface-soft px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium ui-text">{{ $entry['title'] }}</p>
                                <p class="text-xs ui-text-muted">{{ optional($entry['timestamp'])->format('Y-m-d H:i') }}
                                </p>
                            </div>
                            <p class="mt-1 text-xs ui-text-muted">By {{ $entry['actor'] }}</p>
                            @if (!empty($entry['details']))
                                <p class="mt-2 whitespace-pre-line text-sm ui-text">{{ $entry['details'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Workflow History"
                    description="No workflow events have been recorded for this incident yet." />
            @endif
        </x-ui.card>

        <x-ui.card title="Approval History" subtitle="Decision trail by approver role and remarks.">
            @if ($incident->approvals->count() > 0)
                <div class="space-y-3">
                    @foreach ($incident->approvals as $approval)
                        <div class="rounded-xl border ui-border ui-surface-soft px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium ui-text">{{ $approval->approver?->name ?? 'Unknown' }}
                                    ({{ $approval->approver_role }})
                                </p>
                                <p class="text-xs ui-text-muted">{{ optional($approval->decided_at)->format('Y-m-d H:i') }}
                                </p>
                            </div>
                            <p class="mt-1 text-xs ui-text-muted">Decision: {{ $approval->decision }}</p>
                            @if ($approval->remarks)
                                <p class="mt-1 text-sm ui-text">{{ $approval->remarks }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Approval Decisions"
                    description="This incident has not received an approval or rejection decision yet." />
            @endif
        </x-ui.card>

        <x-ui.card title="Comments" subtitle="Collaborative notes from reviewers and reporters.">
            @if ($incident->comments->count() > 0)
                <div class="space-y-3">
                    @foreach ($incident->comments as $comment)
                        <div class="rounded-xl border ui-border ui-surface-soft px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium ui-text">{{ $comment->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs ui-text-muted">{{ $comment->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm ui-text">{{ $comment->comment }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Comments" description="No comments have been added yet." />
            @endif
        </x-ui.card>

        <x-ui.card title="Description">
            <p class="whitespace-pre-line text-sm ui-text">{{ $incident->description }}</p>
        </x-ui.card>

        <x-ui.card title="Attachments" subtitle="Images and files submitted with this incident.">
            @if ($incident->attachments->count() > 0)
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($incident->attachments as $attachment)
                        <div class="rounded-lg border ui-border ui-surface-soft p-3">
                            @if (str_starts_with($attachment->mime_type ?? '', 'image/'))
                                <a href="{{ $attachment->url }}" target="_blank">
                                    <img src="{{ $attachment->url }}" alt="{{ $attachment->original_name }}"
                                        class="h-40 w-full rounded-md object-cover">
                                </a>
                            @endif

                            <a href="{{ $attachment->url }}" target="_blank"
                                class="mt-2 block text-sm ui-text hover:underline">
                                {{ $attachment->original_name }}
                            </a>
                            <p class="mt-0.5 text-xs ui-text-muted">{{ number_format($attachment->size / 1024, 1) }} KB
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Attachments"
                    description="This incident was submitted without any files."></x-ui.empty-state>
            @endif
        </x-ui.card>

        <x-ui.card title="Activity Timeline" subtitle="Tracked updates and status transitions for auditability.">
            @if ($incident->activities->count() > 0)
                <div class="space-y-3">
                    @foreach ($incident->activities as $activity)
                        <div class="rounded-xl border ui-border ui-surface-soft px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium ui-text">
                                    {{ $activity->description ?? ucfirst(str_replace('_', ' ', $activity->action)) }}</p>
                                <p class="text-xs ui-text-muted">{{ $activity->created_at->format('Y-m-d H:i') }}</p>
                            </div>

                            <p class="mt-1 text-xs ui-text-muted">
                                By {{ $activity->user?->name ?? 'System' }}
                            </p>

                            @if (($activity->metadata['from'] ?? null) && ($activity->metadata['to'] ?? null))
                                <p class="mt-1 text-xs ui-text-muted">
                                    Status: {{ $activity->metadata['from'] }} -> {{ $activity->metadata['to'] }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No Timeline Events"
                    description="No updates have been recorded for this incident yet." />
            @endif
        </x-ui.card>
    </div>
@endsection
