@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Edit Training" subtitle="Update training details and multilingual content." />
@endsection

@section('content')
    <form method="POST" action="{{ route('trainings.update', $training) }}">
        @csrf
        @method('PUT')

        @include('trainings.partials.form', [
            'training' => $training,
            'submitLabel' => 'Update Training',
        ])
    </form>
@endsection
