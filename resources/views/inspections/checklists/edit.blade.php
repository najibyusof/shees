@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Edit Inspection Checklist" subtitle="Update checklist builder configuration and translations." />
@endsection

@section('content')
    <form method="POST" action="{{ route('inspection-checklists.update', $checklist) }}">
        @csrf
        @method('PUT')

        @include('inspections.checklists.partials.form', [
            'checklist' => $checklist,
            'submitLabel' => 'Update Checklist',
        ])
    </form>
@endsection
