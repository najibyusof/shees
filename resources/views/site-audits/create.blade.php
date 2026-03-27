@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Schedule Site Audit" subtitle="Plan audit scope, schedule, and baseline status." />
@endsection

@section('content')
    <x-ui.card title="New Audit" subtitle="Define a site performance audit and prepare it for workflow review.">
        <form method="POST" action="{{ route('site-audits.store') }}" class="space-y-4">
            @include('site-audits._form', ['buttonLabel' => 'Create Audit'])
        </form>
    </x-ui.card>
@endsection
