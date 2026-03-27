@props([
    'status' => 'draft',
])

@php
    $normalized = strtolower(str_replace(' ', '_', (string) $status));
    $variant = match ($normalized) {
        'draft' => 'neutral',
        'submitted' => 'info',
        'pending' => 'warning',
        'under_review' => 'warning',
        'approved' => 'success',
        'rejected' => 'error',
        default => 'neutral',
    };
@endphp

<x-ui.badge :variant="$variant" {{ $attributes }}>
    {{ str_replace('_', ' ', ucfirst($normalized)) }}
</x-ui.badge>
