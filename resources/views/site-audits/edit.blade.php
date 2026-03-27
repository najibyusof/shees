@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Edit Site Audit" subtitle="Update schedule, scope, and execution status." />
@endsection

@section('content')
    <x-ui.card title="Edit Audit" subtitle="Only draft/scheduled/in-progress/rejected audits can be edited.">
        <form method="POST" action="{{ route('site-audits.update', $siteAudit) }}" class="space-y-4">
            @method('PUT')
            @include('site-audits._form', ['siteAudit' => $siteAudit, 'buttonLabel' => 'Save Changes'])
        </form>
    </x-ui.card>
@endsection
