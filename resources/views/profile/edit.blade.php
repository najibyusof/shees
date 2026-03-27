@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Profile Settings" subtitle="Manage your account details, security, and access controls." />
@endsection

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        @include('profile.partials.update-profile-information-form')
        @include('profile.partials.update-password-form')
        @include('profile.partials.delete-user-form')
    </div>
@endsection
