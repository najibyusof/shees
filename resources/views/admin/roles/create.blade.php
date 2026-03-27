@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Create Role"
        subtitle="Define a new access role and assign permissions by module from a single control surface." />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6">
        @csrf

        @include('admin.roles.partials.form', [
            'submitLabel' => 'Create Role',
        ])
    </form>
@endsection
