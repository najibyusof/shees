@extends('layouts.app')

@section('header')
    <x-ui.page-header title="Create Training" subtitle="Define a new training program and multilingual details." />
@endsection

@section('content')
    <form method="POST" action="{{ route('trainings.store') }}">
        @csrf

        @include('trainings.partials.form', [
            'training' => null,
            'submitLabel' => 'Create Training',
        ])

        <x-ui.card title="Initial Assignment" subtitle="Optional: assign users immediately after creation." class="mt-6">
            <label for="assigned_user_ids" class="mb-1.5 block text-sm font-medium ui-text-muted">Assign Users</label>
            <select id="assigned_user_ids" name="assigned_user_ids[]" multiple
                class="w-full rounded-lg border ui-border ui-surface px-3 py-2.5 text-sm ui-text shadow-sm">
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(collect(old('assigned_user_ids', []))->contains($user->id))>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </x-ui.card>
    </form>
@endsection
