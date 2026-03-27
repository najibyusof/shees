@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Create Inspection Checklist" subtitle="Define checklist template items and validation rules." />
@endsection

@section('content')
    <form method="POST" action="{{ route('inspection-checklists.store') }}">
        @csrf

        @include('inspections.checklists.partials.form', [
            'checklist' => null,
            'submitLabel' => 'Create Checklist',
        ])
    </form>
@endsection
