@props([
    'eyebrow' => null,
    'title' => '',
    'description' => null,
    'center' => true,
])

<div {{ $attributes->class([$center ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl']) }}>
    @if ($eyebrow)
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-600 dark:text-teal-300">{{ $eyebrow }}</p>
    @endif

    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 dark:text-gray-100 sm:text-4xl">{{ $title }}</h2>

    @if ($description)
        <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-gray-300 sm:text-lg">{{ $description }}</p>
    @endif
</div>
