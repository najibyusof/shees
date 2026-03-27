@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm font-medium text-green-600 dark:text-emerald-300']) }}>
        {{ $status }}
    </div>
@endif
