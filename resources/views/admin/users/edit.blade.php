@extends('layouts.app')

@section('header')
    <x-ui.page-header :title="'Edit ' . $managedUser->name"
        subtitle="Update account details, reset the password if needed, and manage assigned roles." />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        @include('admin.users.partials.form', [
            'managedUser' => $managedUser,
            'roles' => $roles,
            'submitLabel' => 'Save Changes',
        ])
    </form>
@endsection
