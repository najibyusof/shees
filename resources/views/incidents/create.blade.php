@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Create Incident"
        subtitle="Submit notification details first. Complete investigation details after submission." />
@endsection

@section('content')
    @include('incidents.partials.form', [
        'incident' => null,
        'submitLabel' => 'Submit Notification',
        'actionUrl' => route('incidents.store'),
        'formMethod' => 'POST',
        'autosaveCreateUrl' => route('incidents.autosave'),
        'autosaveUpdateTemplate' => url('/incidents/:id/autosave'),
        'updateActionTemplate' => url('/incidents/:id'),
    ])
@endsection
