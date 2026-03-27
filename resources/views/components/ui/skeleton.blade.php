@props([
    'lines' => 3,
    'height' => 'h-4',
])

<div {{ $attributes->merge(['class' => 'space-y-2']) }} role="status" aria-label="Loading">
    @for ($i = 0; $i < $lines; $i++)
        <div class="animate-pulse rounded-md ui-surface-soft {{ $height }}"></div>
    @endfor
</div>
