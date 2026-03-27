@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Audit {{ $siteAudit->reference_no }}"
        subtitle="KPI tracking, NCR root cause analysis, corrective actions, and approval workflow.">
        <x-slot:actions>
            @can('update', $siteAudit)
                <x-ui.button :href="route('site-audits.edit', $siteAudit)" variant="secondary">Edit</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <x-ui.card title="Audit Summary" subtitle="Execution details and workflow state.">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 text-sm">
                <div>
                    <p class="ui-text-muted">Site</p>
                    <p class="font-semibold ui-text">{{ $siteAudit->site_name }}</p>
                </div>
                <div>
                    <p class="ui-text-muted">Area</p>
                    <p class="font-semibold ui-text">{{ $siteAudit->area ?: 'N/A' }}</p>
                </div>
                <div>
                    <p class="ui-text-muted">Status</p>
                    <div class="mt-1"><x-ui.status-badge :status="$siteAudit->status" /></div>
                </div>
                <div>
                    <p class="ui-text-muted">KPI Score</p>
                    <p class="font-semibold ui-text">
                        {{ $siteAudit->kpi_score !== null ? number_format($siteAudit->kpi_score, 1) . '%' : 'N/A' }}</p>
                </div>
            </div>

            <div class="mt-4 text-sm">
                <p class="ui-text-muted">Approval Coverage</p>
                <p class="ui-text">{{ count($approvedRoles) }} / {{ count($requiredApprovalRoles) }} required roles
                    approved.</p>
                @if (count($missingApprovalRoles) > 0)
                    <p class="mt-1 ui-text-muted">Pending roles: {{ implode(', ', $missingApprovalRoles) }}</p>
                @endif
            </div>

            @can('submit', $siteAudit)
                <form method="POST" action="{{ route('site-audits.submit', $siteAudit) }}" class="mt-4">
                    @csrf
                    <x-ui.button type="submit" variant="primary" size="md">Submit for Approval</x-ui.button>
                </form>
            @endcan

            @can('approve', $siteAudit)
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <form method="POST" action="{{ route('site-audits.approve', $siteAudit) }}" class="space-y-2">
                        @csrf
                        <label for="approve_remarks" class="text-sm ui-text-muted">Approval Remarks</label>
                        <textarea id="approve_remarks" name="remarks" rows="2"
                            class="w-full rounded-lg border ui-border px-3 py-2 ui-surface ui-text"></textarea>
                        <x-ui.button type="submit" variant="primary" size="sm">Approve</x-ui.button>
                    </form>

                    <form method="POST" action="{{ route('site-audits.reject', $siteAudit) }}" class="space-y-2">
                        @csrf
                        <label for="reject_reason" class="text-sm ui-text-muted">Rejection Reason</label>
                        <textarea id="reject_reason" name="reason" rows="2" required
                            class="w-full rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text"></textarea>
                        <x-ui.button type="submit" variant="danger" size="sm">Reject</x-ui.button>
                    </form>
                </div>
            @endcan
        </x-ui.card>

        <x-ui.card title="KPI Tracking" subtitle="Define measurable KPIs and monitor actual vs target performance.">
            <form method="POST" action="{{ route('site-audits.kpis.store', $siteAudit) }}"
                class="grid gap-3 md:grid-cols-6">
                @csrf
                <input type="text" name="name" required placeholder="KPI name"
                    class="md:col-span-2 rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                <input type="number" step="0.01" name="target_value" placeholder="Target"
                    class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                <input type="number" step="0.01" name="actual_value" placeholder="Actual"
                    class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                <input type="number" name="weight" min="1" max="10" value="1"
                    class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                <x-ui.button type="submit" variant="primary" size="sm">Add KPI</x-ui.button>
            </form>

            @if ($siteAudit->kpis->count() > 0)
                <x-ui.table class="mt-4">
                    <x-slot name="head">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Target</th>
                            <th class="px-4 py-3">Actual</th>
                            <th class="px-4 py-3">Weight</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </x-slot>

                    @foreach ($siteAudit->kpis as $kpi)
                        <tr>
                            <td class="px-4 py-3 ui-text">{{ $kpi->name }}</td>
                            <td class="px-4 py-3 ui-text">{{ $kpi->target_value ?? 'N/A' }}</td>
                            <td class="px-4 py-3 ui-text">{{ $kpi->actual_value ?? 'N/A' }}</td>
                            <td class="px-4 py-3 ui-text">{{ $kpi->weight }}</td>
                            <td class="px-4 py-3 ui-text">{{ ucfirst($kpi->status) }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>

        <x-ui.card title="NCR & Root Cause Analysis"
            subtitle="Capture non-conformance details and corrective action plans.">
            <form method="POST" action="{{ route('site-audits.ncrs.store', $siteAudit) }}"
                class="grid gap-3 md:grid-cols-2">
                @csrf
                <input type="text" name="title" required placeholder="NCR title"
                    class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                <select name="severity" class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text">
                    <option value="minor">Minor</option>
                    <option value="major">Major</option>
                    <option value="critical">Critical</option>
                </select>
                <textarea name="description" rows="2" required placeholder="Non-conformance details"
                    class="md:col-span-2 rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text"></textarea>
                <textarea name="root_cause" rows="2" placeholder="Root cause analysis"
                    class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text"></textarea>
                <textarea name="corrective_action_plan" rows="2" placeholder="Corrective action plan"
                    class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text"></textarea>
                <div class="md:col-span-2">
                    <x-ui.button type="submit" variant="primary" size="sm">Add NCR</x-ui.button>
                </div>
            </form>

            <div class="mt-5 space-y-4">
                @forelse ($siteAudit->ncrReports as $ncr)
                    <div class="rounded-xl border ui-border p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold ui-text">{{ $ncr->reference_no }} - {{ $ncr->title }}</p>
                            <div class="flex items-center gap-2">
                                <x-ui.badge :variant="match ($ncr->severity) { 'critical' => 'error', 'major' => 'warning', default => 'info' }">{{ ucfirst($ncr->severity) }}</x-ui.badge>
                                <x-ui.status-badge :status="$ncr->status === 'pending_verification' ? 'under_review' : $ncr->status" />
                            </div>
                        </div>
                        <p class="mt-2 text-sm ui-text-muted">{{ $ncr->description }}</p>
                        <p class="mt-2 text-sm ui-text"><strong>Root Cause:</strong> {{ $ncr->root_cause ?: 'N/A' }}</p>
                        <p class="text-sm ui-text"><strong>Corrective Plan:</strong>
                            {{ $ncr->corrective_action_plan ?: 'N/A' }}</p>

                        <form method="POST" action="{{ route('ncr-reports.corrective-actions.store', $ncr) }}"
                            class="mt-3 grid gap-2 md:grid-cols-3">
                            @csrf
                            <input type="text" name="title" required placeholder="Corrective action title"
                                class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                            <input type="date" name="due_date"
                                class="rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text" />
                            <x-ui.button type="submit" variant="secondary" size="sm">Add Action</x-ui.button>
                            <textarea name="description" rows="2" required placeholder="Action description"
                                class="md:col-span-3 rounded-lg border ui-border px-3 py-2.5 text-sm ui-surface ui-text"></textarea>
                        </form>

                        @if ($ncr->correctiveActions->count() > 0)
                            <ul class="mt-3 space-y-2">
                                @foreach ($ncr->correctiveActions as $action)
                                    <li class="rounded-md border ui-border p-3 text-sm">
                                        <p class="font-medium ui-text">{{ $action->title }}</p>
                                        <p class="ui-text-muted">{{ $action->description }}</p>
                                        <p class="mt-1 ui-text">Status:
                                            {{ ucfirst(str_replace('_', ' ', $action->status)) }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state title="No NCR Reports"
                        description="Create an NCR to start root cause analysis and corrective action tracking." />
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Approval History" subtitle="Decision trail by approver role.">
            @if ($siteAudit->approvals->count() > 0)
                <ul class="space-y-2">
                    @foreach ($siteAudit->approvals as $approval)
                        <li class="rounded-lg border ui-border p-3 text-sm">
                            <p class="font-medium ui-text">{{ $approval->approver?->name ?? 'Unknown' }}
                                ({{ $approval->approver_role }})</p>
                            <p class="ui-text-muted">{{ optional($approval->decided_at)->format('Y-m-d H:i') }} -
                                {{ ucfirst($approval->decision) }}</p>
                            @if ($approval->remarks)
                                <p class="mt-1 ui-text">{{ $approval->remarks }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <x-ui.empty-state title="No Approval Decisions"
                    description="No approval actions have been recorded yet." />
            @endif
        </x-ui.card>
    </div>
@endsection
