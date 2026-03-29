@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Edit Incident" :subtitle="'Ref: ' .
        ($incident->incident_reference_number ?? 'N/A') .
        '  •  ' .
        ($incident->incidentType?->name ?? ($incident->classification ?? 'Incident'))" />
@endsection

@section('content')
    @include('incidents.partials.form', [
        'incident' => $incident,
        'submitLabel' => 'Update Incident',
        'actionUrl' => route('incidents.update', $incident),
        'formMethod' => 'PUT',
        'autosaveCreateUrl' => route('incidents.autosave'),
        'autosaveUpdateTemplate' => url('/incidents/:id/autosave'),
        'updateActionTemplate' => url('/incidents/:id'),
    ])
@endsection
