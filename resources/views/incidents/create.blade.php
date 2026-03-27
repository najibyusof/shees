@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Create Incident" subtitle="Log a new incident with supporting details and files." />
@endsection

@section('content')
    <form method="POST" action="{{ route('incidents.store') }}" enctype="multipart/form-data">
        @csrf

        @include('incidents.partials.form', [
            'incident' => null,
            'classifications' => $classifications,
            'submitLabel' => 'Create Incident',
        ])
    </form>
@endsection
