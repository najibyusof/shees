@props([
    'title' => 'Analytics',
    'data' => ['labels' => [], 'data' => []],
    'type' => 'bar',
    'label' => 'Dataset',
    'size' => 'md',
    'height' => '220px',
    'lazy' => false,
    'compact' => true,
    'expand' => false,
    'revealDelay' => 0,
    'bodyMinHeight' => '210px',
])

@php
    $sizeClass = $expand
        ? 'col-span-12'
        : match ($size) {
            'sm' => 'col-span-12 md:col-span-6 xl:col-span-4',
            'lg' => 'col-span-12',
            default => 'col-span-12 md:col-span-6',
        };

    $paddingClass = $compact ? 'p-3' : 'p-4';
@endphp

<article data-analytics-reveal data-reveal-delay="{{ (int) $revealDelay }}"
    {{ $attributes->merge(['class' => 'analytics-reveal ' . $sizeClass . ' rounded-xl border ui-border bg-white shadow-sm dark:bg-gray-900']) }}>
    <div class="{{ $paddingClass }}" style="min-height: {{ $bodyMinHeight }};">
        <h3 class="mb-2 text-sm font-semibold text-slate-600 dark:text-slate-200">{{ $title }}</h3>
        <x-chart :type="$type" :data="$data" :label="$label" :height="$height" :lazy="$lazy" />
    </div>
</article>
