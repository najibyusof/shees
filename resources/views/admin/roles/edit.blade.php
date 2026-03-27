@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="'Edit ' . $role->name"
        subtitle="Refine role metadata and permission coverage without leaving the admin workspace." />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        @include('admin.roles.partials.form', [
            'submitLabel' => 'Save Changes',
        ])
    </form>
@endsection
