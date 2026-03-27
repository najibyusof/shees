@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Create User"
        subtitle="Provision a new account and assign the roles required for platform access." />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
        @csrf

        @include('admin.users.partials.form', [
            'roles' => $roles,
            'submitLabel' => 'Create User',
        ])
    </form>
@endsection
