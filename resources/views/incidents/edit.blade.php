@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Edit Incident" subtitle="Update incident details and manage attachments." />
@endsection

@section('content')
    <form method="POST" action="{{ route('incidents.update', $incident) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('incidents.partials.form', [
            'incident' => $incident,
            'classifications' => $classifications,
            'submitLabel' => 'Update Incident',
        ])
    </form>
@endsection
